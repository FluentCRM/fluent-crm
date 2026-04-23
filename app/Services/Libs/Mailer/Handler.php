<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\SubscriberPivot;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class Handler extends BaseHandler
{
    protected $runnerTitle = 'Handler::handle';

    protected $sendingPerChunk = 20;

    protected $maximumProcessingTime = 50;

    protected $optionKey = 'fluentcrm_is_sending_emails';

    public function handle()
    {
        if (!$this->isSystemOk()) {
            return true; // Early return
        }

        Helper::debugLog('Running Scheduler -> ' . $this->calledFrom, 'Handler::handle');

        Helper::maybeDisableEmojiOnEmail();

        try {
            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            $result = $this->processBatchEmails();

            if (is_wp_error($result)) {
                Helper::debugLog('Error at Mailer::handle', $result->get_error_message(), 'error');
                update_option($this->optionKey, null);
                $this->logSentCount();
                return true;
            }

            if ($result === 'time_up') {
                $this->callBackGround();
                $this->logSentCount();
                return true;
            }
        } catch (\Exception $e) {
            Helper::debugLog('Exception at Mailer::handle', $e->getMessage(), 'error');
        }

        $this->logSentCount();

        update_option($this->optionKey, null);

        if ($this->sentCount || random_int(0, 50) > 20) { // sometimes we want to check this
            $lastChecked = fluentCrmGetOptionCache('_fcrm_last_email_process_cleanup', 600);
            if (!$lastChecked || time() - $lastChecked > 70) {
                $dateStamp = date('Y-m-d H:i:s', (current_time('timestamp') - $this->maximumProcessingTime - 30));
                CampaignEmail::where('status', 'processing')
                    ->where('updated_at', '<', $dateStamp)
                    ->update([
                        'status' => 'pending'
                    ]);
                fluentCrmSetOptionCache('_fcrm_last_email_process_cleanup', time(), 600);
            }
        }

        if (!$this->sentCount) {
            do_action('fluentcrm_scheduled_maybe_regular_tasks');
            do_action('fluent_crm_process_automation');
        }

        return true;
    }

    private function isSystemOk()
    {
        $this->calledFrom = Arr::get($_REQUEST, 'action') == 'fluentcrm-post-campaigns-send-now' ? 'ajax' : 'cron';

        if ($this->calledFrom == 'cron') {
            fluentcrm_update_option($this->optionKey . '_last_called', time());
        }

        if (did_action('fluent_crm/sending_emails_starting') || apply_filters('fluent_crm/disable_email_processing', false)) {
            return false;
        }

        $this->startingTimeStamp = time();
        $this->isMultiThread = Helper::willMultiThreadEmail();

        if ($this->isMultiThread) {
            if (!as_next_scheduled_action('fluent_crm_send_multi_thread_emails')) {
                Helper::debugLog('Scheduling multi thread emails', 'extended log');
                as_schedule_recurring_action(time(), 60, 'fluent_crm_send_multi_thread_emails', [], 'fluent-crm', false);
            }
        }

        if ($this->memoryExceeded()) {
            Helper::debugLog('Mailer Memory Exceeded at ' . $this->runnerTitle, 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true));
            return false;
        }

        $systemMaxProcessingTime = fluentCrmMaxRunTime();

        if ($this->maximumProcessingTime > $systemMaxProcessingTime) {
            $this->maximumProcessingTime = $systemMaxProcessingTime;
        }

        if ($this->isProcessing()) {
            return false;
        }

        return true;
    }

    protected function getNextBatchEmails()
    {
        $currentTime = current_time('mysql');

        $emails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', $currentTime)
            ->with('campaign', 'subscriber')
            ->orderBy('scheduled_at', 'DESC')
            ->limit($this->sendingPerChunk)
            ->get();

        $ids = $emails->pluck('id')->toArray();

        if ($ids) {
            fluentCrmDb()->table('fc_campaign_emails')
                ->whereIn('id', $ids)
                ->update([
                    'status'     => 'processing',
                    'updated_at' => $currentTime
                ]);
        }

        return $emails;
    }

    public function processSubscriberEmail($subscriberId)
    {
        if (!$this->isSystemOk()) {
            return;
        }

        $emailCollection = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', current_time('mysql'))
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->where('subscriber_id', $subscriberId)
            ->get();

        $ids = $emailCollection->pluck('id')->toArray();

        if ($ids) {
            CampaignEmail::whereIn('id', $ids)
                ->update([
                    'status'     => 'processing',
                    'updated_at' => current_time('mysql')
                ]);
            $this->sendEmails($emailCollection);
        }
    }

    public function sendDoubleOptInEmail($subscriber)
    {
        if ($subscriber->status == 'subscribed' || !$subscriber->email) {
            return false; // already subscribed
        }

        $listIdOfSubscriber = Helper::latestListIdOfSubscriber($subscriber->id);
        $config = null;
        if ($listIdOfSubscriber) {
            $globalDoubleOptin = fluentcrm_get_list_meta($listIdOfSubscriber, 'global_double_optin');
            if ($globalDoubleOptin && $globalDoubleOptin->value == 'no') {
                $meta = fluentcrm_get_meta($listIdOfSubscriber, 'FluentCrm\App\Models\Lists', 'double_optin_settings', []);
                $config = $meta ? $meta->value : null;
            }
        }

        if (!$config) {
            $config = Helper::getDoubleOptinSettings();
        }

        if (!Arr::get($config, 'email_subject') || !Arr::get($config, 'email_body')) {
            return false; // is not valid
        }

        $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $config['email_body'], $subscriber);
        $emailSubject = apply_filters('fluent_crm/parse_campaign_email_text', $config['email_subject'], $subscriber);

        $emailPreHeader = '';
        if (Arr::get($config, 'email_pre_header')) {
            $emailPreHeader = apply_filters('fluent_crm/parse_campaign_email_text', $config['email_pre_header'], $subscriber);
        }

        $url = site_url('?fluentcrm=1&route=confirmation&hash=' . $subscriber->hash . '&secure_hash=' . $subscriber->getSecureHash());

        $emailBody = apply_filters('fluent_crm/double_optin_email_body', $emailBody, $subscriber);
        $emailSubject = apply_filters('fluent_crm/double_optin_email_subject', $emailSubject, $subscriber);
        $emailPreHeader = apply_filters('fluent_crm/double_optin_email_pre_header', $emailPreHeader, $subscriber);

        $emailBody = str_replace('#activate_link#', $url, $emailBody);

        $templateData = [
            'preHeader'   => $emailPreHeader,
            'email_body'  => $emailBody,
            'footer_text' => '',
            'config'      => Helper::getTemplateConfig($config['design_template'], false)
        ];

        $emailBody = apply_filters(
            'fluent_crm/email-design-template-' . $config['design_template'],
            $emailBody,
            $templateData,
            false,
            $subscriber
        );

        if (strpos($emailBody, '##crm.') || strpos($emailBody, '{{crm.')) {
            // we have CRM specific smartcodes
            $emailBody = apply_filters('fluent_crm/parse_extended_crm_text', $emailBody, $subscriber);
        }

        $data = [
            'to'      => [
                'email' => $subscriber->email,
                'name'  => $subscriber->full_name
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeader()
        ];

        Helper::maybeDisableEmojiOnEmail();
        Mailer::send($data, $subscriber);
        return true;
    }

    private function callBackGround()
    {
        if ($this->memoryExceeded()) {
            Helper::debugLog('Handler::callBackGround Memory Exceeded', 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true), 'info');
            return false;
        }

        $nextCron = as_next_scheduled_action('fluentcrm_scheduled_every_minute_tasks');
        $willRun = !$nextCron || $nextCron == 1 || ($nextCron - time()) >= 5 || ($nextCron - time()) < -70;

        if (!$willRun) {
            $lastCalled = (int)fluentcrm_get_option($this->optionKey . '_last_called');
            if ($lastCalled && (time() - $lastCalled) < 50) {
                $willRun = true;
            }
        }

        if ($willRun) { // If next cron is after more than 5 seconds we want to run this or it's currently running

            $url = add_query_arg([
                'action' => 'fluentcrm-post-campaigns-send-now',
                'time'   => time()
            ], admin_url('admin-ajax.php'));

            Helper::debugLog('Sent to Background Handler::callBackGround', $url, 'extended');

            wp_remote_post($url, [
                'sslverify' => false,
                'blocking'  => false,
                'timeout'   => 1,
                'body'      => [
                    'campaign_id' => null,
                    'retry'       => 1
                ]
            ]);
        } else {
            Helper::debugLog('Not Running', 'Handler::callBackGround -> ' . ($nextCron - time()), 'extended');
        }
    }

    protected function isTimeUp()
    {
        return (time() - $this->startingTimeStamp) >= $this->maximumProcessingTime;
    }
}
