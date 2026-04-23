<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\CampaignProcessor;
use FluentCrm\App\Services\ExternalIntegrations\Maintenance;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\FileSystem;
use FluentCrm\App\Services\Libs\Mailer\Handler;
use FluentCrm\App\Services\Libs\Mailer\MultiThreadHandler;

/**
 *  Scheduler Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class Scheduler
{

    public static function register()
    {
        /*
         * Migrating from CRON to Action Scheduler for Every Minutes Tasks
         */
        add_action('fluentcrm_scheduled_minute_tasks', function () {
            if (!as_next_scheduled_action('fluentcrm_scheduled_every_minute_tasks')) {
                Helper::debugLog('Migrating Every Minute CRON to Action Scheduler for FluentCRM');
                as_schedule_recurring_action(time(), 60, 'fluentcrm_scheduled_every_minute_tasks', [], 'fluent-crm');
                return;
            }

            // We already have the action scheduled, but that can be blocked by some other plugin
            $lastScheduler = fluentCrmGetOptionCache('_fcrm_last_scheduler');
            if (($lastScheduler && (time() - $lastScheduler) > 70)) {
                Helper::debugLog('Action scheduler is not working', 'Scheduler::register -> ' . (time() - $lastScheduler));
                self::process();
                return;
            }

            do_action('fluent_crm_process_automation');

            // Looks like action scheduler is working just fine. Maybe we can check for some regular tasks
            // We will run the five minutes tasks for around 60% times
            if (mt_rand(1, 100) > 40) {
                self::processFiveMinutes();
            }

        });

        // This is required to instantly send emails for regular email handler
        add_action('wp_ajax_nopriv_fluentcrm-post-campaigns-send-now', function () {
            if (!get_option('fluentcrm_is_sending_emails')) {
                $nextCron = as_next_scheduled_action('fluentcrm_scheduled_every_minute_tasks');
                $willRun = !$nextCron || $nextCron == 1 || ($nextCron - time()) >= 3 || ($nextCron - time()) < -70;

                // $willRun will be true if the next cron is not scheduled or it is scheduled for more than 3 seconds
                // or it is scheduled for less than -70 seconds (which means it is already passed)
                // or if the next cron is scheduled for 1 second (which means it is already passed)

                if (!$willRun) {
                    $lastCalled = (int)fluentcrm_get_option('fluentcrm_is_sending_emails_last_called');
                    if ($lastCalled && (time() - $lastCalled) < 52) {
                        $willRun = true;
                    }
                }

                if ($willRun) {
                    Helper::debugLog('AJAX: post-campaigns-send-now', 'Timing: ' . ($nextCron - time()), 'extended');
                    $mailer = new \FluentCrm\App\Services\Libs\Mailer\Handler();
                    $mailer->handle();
                }
            }

            nocache_headers();
            wp_send_json_success([
                'message'   => 'success',
                'timestamp' => time()
            ]);
        });

        // For Multi Threaded Emails Internal Ajax
        add_action('wp_ajax_nopriv_fluentcrm-post-multi-thread-send-now', function () {

            if (!get_option('fluentcrm_is_sending_multi_emails')) {
                $nextCron = as_next_scheduled_action('fluent_crm_send_multi_thread_emails');
                $willRun = !$nextCron || $nextCron == 1 || ($nextCron - time()) >= 3 || ($nextCron - time()) < -70;

                if (!$willRun) {
                    $lastCalled = (int)fluentcrm_get_option('fluentcrm_is_sending_multi_emails_last_called');
                    if ($lastCalled && (time() - $lastCalled) < 52) {
                        $willRun = true;
                    }
                }

                if ($willRun) {
                    if (Helper::isExperimentalEnabled('multi_threading_emails')) {
                        (new MultiThreadHandler())->handle();
                    }
                }
            }

            nocache_headers();
            wp_send_json_success([
                'message'   => 'success',
                'timestamp' => time()
            ]);
        });

        add_action('fluentcrm_scheduled_every_minute_tasks', array(__CLASS__, 'process'));
        add_action('fluentcrm_scheduled_hourly_tasks', array(__CLASS__, 'processHourly'));
        add_action('fluentcrm_scheduled_five_minute_tasks', array(__CLASS__, 'processFiveMinutes'));
        add_action('fluentcrm_process_contact_jobs', array(__CLASS__, 'processForSubscriber'), 999, 1);
        add_action('fluentcrm_scheduled_weekly_tasks', array(__CLASS__, 'processWeekly'));
        add_action('fluent_crm_send_multi_thread_emails', array(__CLASS__, 'processMultiThreadEmails'), 10);

        add_action('fluent_crm_cancel_multi_thread_mailing', function () {
            as_unschedule_all_actions('fluent_crm_send_multi_thread_emails');
            return true;
        });

        /*
         *  Clean up schedule that means removing from database-  tasks by action scheduler
         * Clean up before last 7 days logs generated by action scheduler
         * this action will be triggered daily and will remove all the logs generated before 7 days
         */
        add_action('fluent_crm_ascheduler_runs_daily', function () {
            Cleanup::maybeRemoveOldScheuledActionLogs();
        });

    }

    public static function process()
    {
        
        wp_raise_memory_limit('admin');
        $lastScheduler = fluentCrmGetOptionCache('_fcrm_last_scheduler');

        if (($lastScheduler && (time() - $lastScheduler) < 30) || did_action('fluentcrm_process_scheduled_tasks_init')) {
            return false; // it's too fast. We don't want to run this again within 30 seconds
        }

        fluentCrmSetOptionCache('_fcrm_last_scheduler', time(), 50);
        do_action('fluentcrm_process_scheduled_tasks_init');

        // Send Pending Emails
        (new Handler)->handle();
        return true;
    }

    public static function processForSubscriber($subscriber)
    {
        if (!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            // @todo: Implement this immediately
            (new Handler)->processSubscriberEmail($subscriber->id);
        }
    }

    public static function processHourly()
    {
        self::markArchiveCampaigns();
        self::maybeCleanupCsvFiles();
        do_action('fluent_crm_process_automation');
    }


    public static function markArchiveCampaigns()
    {
        // get the scheduled or working  campaigns where scheduled_at is five minutes ago
        $campaigns = Campaign::whereIn('status', ['working', 'scheduled'])
            ->whereDoesntHave('emails', function ($query) {
                $query->whereIn('status', ['scheduling', 'pending', 'scheduled', 'processing', 'draft']);
                return $query;
            })
            ->withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->where('scheduled_at', '<', gmdate('Y-m-d H:i:s', current_time('timestamp') - 300))
            ->get();

        if (!$campaigns->isEmpty()) {

            Campaign::whereIn('id', array_unique($campaigns->pluck('id')->toArray()))
                ->withoutGlobalScope('type')
                ->update([
                    'status' => 'archived'
                ]);

            foreach ($campaigns as $campaign) {
                do_action('fluent_crm/campaign_archived', $campaign);
            }

            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public static function processWeekly()
    {
        (new Maintenance())->maybeProcessData();

        fluentCrmDb()->table('fc_campaign_emails')
            ->where('status', 'sent')
            ->where('email_body', '!=', '')
            ->update([
                'email_body' => ''
            ]);
    }

    /**
     * @return bool
     */

    public static function processFiveMinutes()
    {
        $lastRun = fluentCrmGetOptionCache('_fcrm_last_five_minutes_run', 30);

        if ($lastRun && (time() - $lastRun) < 60) {
            return false;
        }

        fluentCrmSetOptionCache('_fcrm_last_five_minutes_run', time(), 30);

        $lastChecked = fluentCrmGetOptionCache('_fcrm_last_email_process_cleanup', 600);
        if (!$lastChecked || time() - $lastChecked > 140) {
            $dateStamp = gmdate('Y-m-d H:i:s', (current_time('timestamp') - 120));
            CampaignEmail::where('status', 'processing')
                ->where('updated_at', '<', $dateStamp)
                ->update([
                    'status' => 'pending'
                ]);

            fluentCrmSetOptionCache('_fcrm_last_email_process_cleanup', time(), 600);
        }

        CampaignEmail::where('status', 'processing')
            ->where('updated_at', '<', gmdate('Y-m-d H:i:s', (current_time('timestamp') - 30)))
            ->update([
                'status' => 'pending'
            ]);

        $cutOutTime = date('Y-m-d H:i:s', current_time('timestamp') + 360); // within 6 minutes of the future

        $campaigns = Campaign::whereIn('status', ['pending-scheduled', 'processing'])
            ->withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->orderBy('scheduled_at', 'DESC')
            ->where('scheduled_at', '<=', $cutOutTime)
            ->limit(2)
            ->get();

        if ($campaigns->isEmpty()) {
            do_action('fluent_crm_process_automation');
            do_action('fluentcrm_scheduled_hourly_tasks');
            return false;
        }

        $firstCampaign = $campaigns[0];

        if ($firstCampaign->status == 'pending-scheduled') {
            $firstCampaign->status = 'processing';
            $firstCampaign->save();
        }

        $runTime = fluentCrmMaxRunTime() - 5;
        $campaign = (new CampaignProcessor($firstCampaign->id))->processEmails(20, $runTime);

        if (fluentCrmIsMemoryExceeded()) {
            return false;
        }

        if (($campaign && $campaign->status == 'processing') || count($campaigns) > 1) {
            // Send a background request here
            wp_remote_post(admin_url('admin-ajax.php'), [
                'sslverify' => false,
                'blocking'  => false,
                'cookies'   => array(),
                'body'      => [
                    'retry'  => 1,
                    'time'   => time(),
                    'action' => 'fluentcrm-post-campaigns-emails-processing'
                ]
            ]);
            return true;
        }

        return false;
    }

    public static function maybeCleanupCsvFiles()
    {
        $dir = FileSystem::getDir();

        // loop through files in directory
        foreach (glob($dir . '/fluentcrm-*.csv') as $filename) {
            // check if file was created before last 30 minutes
            if (time() - filectime($filename) >= 1800) {
                @unlink($filename); // delete file
            }
        }
    }

    public static function processMultiThreadEmails()
    {
        (new MultiThreadHandler())->handle();
        return true;
    }
}
