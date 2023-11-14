<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Services\CampaignProcessor;
use FluentCrm\App\Services\ExternalIntegrations\Maintenance;
use FluentCrm\App\Services\Libs\FileSystem;
use FluentCrm\App\Services\Libs\Mailer\Handler;

/**
 *  Scheduler Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class Scheduler
{
    public static function process()
    {
        wp_raise_memory_limit('admin');

        $lastScheduler = get_option('_fcrm_last_scheduler');

        if ($lastScheduler && (time() - $lastScheduler) < 30) {
            return false; // it's too fast. We don't want to run this again within 30 seconds
        }

        update_option('_fcrm_last_scheduler', time(), 'no');
        do_action('fluentcrm_process_scheduled_tasks_init');

        // Send Pending Emails
        (new Handler)->handle();
    }

    public static function processForSubscriber($subscriber)
    {
        if (!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            (new Handler)->processSubscriberEmail($subscriber->id);
        }
    }

    public static function processHourly()
    {
        // cleanup campaigns
        (new Handler)->finishProcessing();

        self::maybeCleanupCsvFiles();
    }

    /**
     * @return void
     */
    public static function processWeekly()
    {
        (new Maintenance())->maybeProcessData();
    }

    /**
     * @return bool
     */

    public static function processFiveMinutes()
    {
        $cutOutTime = date('Y-m-d H:i:s', current_time('timestamp') + 360); // within 6 minutes of the future

        $campaigns = Campaign::whereIn('status', ['pending-scheduled', 'processing'])
            ->withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->orderBy('scheduled_at', 'DESC')
            ->where('scheduled_at', '<=', $cutOutTime)
            ->limit(2)
            ->get();

        if ($campaigns->isEmpty()) {
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
            // check if file was created before last 15 minutes
            if (time() - filectime($filename) >= 900) {
                @unlink($filename); // delete file
            }
        }
    }
}
