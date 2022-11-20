<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class Handler
{
    protected $startedAt = null;

    protected $campaignId = false;

    protected $emailLimitPerSecond = 0;

    protected $maximumProcessingTime = 50;

    protected $dispatchedWithinOneSecond = 0;

    protected $hadJobs = false;

    public function handle($campaignId = null)
    {
        if (did_action('fluentcrm_sending_emails_starting')) {
            return false;
        }

        if (apply_filters('fluentcrm_disable_email_processing', false)) {
            return false;
        }

        $this->maximumProcessingTime = apply_filters('fluentcrm_max_email_sending_time', 50);

        $sendingPerChunk = apply_filters('fluentcrm_email_sending_per_chunk', 20);

        Helper::maybeDisableEmojiOnEmail();

        try {
            $this->campaignId = $campaignId;
            if ($this->isProcessing()) {
                return false;
            }

            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            $startedTimeStamp = time();
            $result = $this->processBatchEmails($startedTimeStamp, $campaignId, $sendingPerChunk);

            if ($result === 'time_up') {
                return false;
            }

        } catch (\Exception $e) {
            // vdd($e);
        }

        $hadJobs = $this->hadJobs;
        if ($hadJobs || mt_rand(0, 50) > 20) { // sometimes we want to check this
            $dateStamp = date('Y-m-d H:i:s', (current_time('timestamp') - $this->maximumProcessingTime - 30));
            CampaignEmail::where('status', 'processing')
                ->where('updated_at', '<', $dateStamp)
                ->update([
                    'status'       => 'pending',
                    'scheduled_at' => current_time('mysql')
                ]);
        }

        update_option(FLUENTCRM . '_is_sending_emails', null);

        if (!$hadJobs) {
            do_action('fluentcrm_scheduled_maybe_regular_tasks');
        }
    }

    protected function processBatchEmails($startedTimeStamp, $campaignId = null, $perBatch = 10)
    {
        if ((time() - $startedTimeStamp) > $this->maximumProcessingTime) {
            update_option(FLUENTCRM . '_is_sending_emails', null);
            if (!$this->memory_exceeded()) {
                $this->callBackGround();
            }
            return 'time_up';
        }

        $emails = $this->getNextBatchEmails($campaignId, $perBatch);

        if ($emails->isEmpty()) {
            update_option(FLUENTCRM . '_is_sending_emails', null);
            return 'empty';
        }

        $this->hadJobs = true;
        $this->updateProcessTime();
        $this->sendEmails($emails);
        usleep(5000); // 5 miliseconds sleep

        return $this->processBatchEmails($startedTimeStamp, $campaignId, $perBatch);
    }

    protected function getNextBatchEmails($campaignId = null, $limit = 10)
    {
        $currentTime = current_time('mysql');

        $emails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->when($campaignId, function ($query) use ($campaignId) {
                return $query->where('campaign_id', $campaignId);
            })
            ->where('scheduled_at', '<=', $currentTime)
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->orderBy('scheduled_at', 'ASC')
            ->limit($limit)
            ->get();

        $ids = $emails->pluck('id')->toArray();

        if ($ids) {
            fluentCrmDb()->table('fc_campaign_emails')
                ->whereIn('id', $ids)
                ->update([
                    'status'       => 'processing',
                    'updated_at'   => $currentTime,
                    'scheduled_at' => $currentTime
                ]);
        }

        return $emails;
    }

    public function processSubscriberEmail($subscriberId)
    {
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

    protected function isProcessing()
    {
        $lastProcessStartedAt = get_option(FLUENTCRM . '_is_sending_emails');

        if (!$lastProcessStartedAt) {
            return false;
        }

        if ($this->seemsStuck($lastProcessStartedAt)) {
            return false;
        }

        return true;
    }

    protected function processing()
    {
        update_option(FLUENTCRM . '_is_sending_emails', time());
    }

    protected function updateProcessTime()
    {
        update_option(FLUENTCRM . '_is_sending_emails', time());
    }

    protected function seemsStuck($lastProcessStartedAt)
    {
        if ($lastProcessStartedAt && time() - $lastProcessStartedAt > 60) {
            return true;
        }
        return false;
    }

    protected function sendEmails($campaignEmails)
    {
        do_action('fluentcrm_sending_emails_starting', $campaignEmails);

        if (defined('FLUENTMAIL')) {
            add_filter('fluentmail_will_log_email', 'fluentcrm_maybe_disable_fsmtp_log', 10, 2);
        }

        $failedIds = [];
        $sentIds = [];

        foreach ($campaignEmails as $email) {
            if ($this->reachedEmailLimitPerSecond()) {
                $this->updateEmailsStatus($sentIds, 'sent');
                $sentIds = [];
                $this->updateEmailsStatus($failedIds, 'failed');
                $failedIds = [];
                $this->restartWhenOneSecondExceeds();
            }

            $response = Mailer::send($email->data());

            $this->dispatchedWithinOneSecond++;

            if (is_wp_error($response)) {
                $failedIds[] = $email->id;
            } else {
                $sentIds[] = $email->id;
            }
        }

        $this->updateEmailsStatus($sentIds, 'sent');
        $this->updateEmailsStatus($failedIds, 'failed');

        if (defined('FLUENTMAIL')) {
            remove_filter('fluentmail_will_log_email', 'fluentcrm_maybe_disable_fsmtp_log', 10);
        }

        do_action('fluentcrm_sending_emails_done', $campaignEmails);
    }

    protected function reachedEmailLimitPerSecond()
    {
        $emailLimitPerSecond = $this->getEmailLimitPerSecond();
        return ($emailLimitPerSecond && $this->dispatchedWithinOneSecond >= $emailLimitPerSecond);
    }

    protected function restartWhenOneSecondExceeds()
    {
        $sleepMicroSecond = 1000000 - (microtime(true) - $this->startedAt) * 1000000;
        if ($sleepMicroSecond > 0) {
            usleep(ceil($sleepMicroSecond));
        }

        $this->dispatchedWithinOneSecond = 0;
        $this->startedAt = microtime(true);
    }

    protected function getEmailLimitPerSecond()
    {
        if ($this->emailLimitPerSecond) {
            return $this->emailLimitPerSecond;
        }

        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        if (!empty($emailSettings['emails_per_second'])) {
            $limit = intval($emailSettings['emails_per_second']);
        } else {
            $limit = 14;
        }

        if (!$limit || $limit < 2) {
            $limit = 2;
        }

        $this->emailLimitPerSecond = $limit;
    }

    public function finishProcessing()
    {
        $this->markArchiveCampaigns();
        //  $this->jobCompleted();
    }

    protected function markArchiveCampaigns()
    {
        // get the scheduled or working  campaigns where scheduled_at is five minutes ago
        $campaigns = Campaign::whereIn('status', ['working', 'scheduled'])->whereDoesntHave('emails', function ($query) {
            $query->whereIn('status', ['scheduling', 'pending', 'scheduled', 'processing', 'draft']);
        })
            ->where('scheduled_at', '<', date('Y-m-d H:i:s', current_time('timestamp') - 300))
            ->get();

        if (!$campaigns->isEmpty()) {
            Campaign::whereIn('id', array_unique($campaigns->pluck('id')->toArray()))
                ->update([
                    'status' => 'archived'
                ]);
        }
    }

    protected function jobCompleted()
    {
        // If we've still some campaigns in working mode then they are stuck so
        // Mark those campaigns and their pending emails as purged, so we can show
        // those campaigns in the campaign's page (index) allowed to edit the campaign.
        foreach (Campaign::where('status', 'working')->get() as $campaign) {

            $hasPending = $campaign->emails()->whereIn('status', ['draft', 'pending', 'scheduled', 'processing'])->count();
            if ($hasPending) {
                continue;
            }

            $hasSent = $campaign->emails()->where('status', 'sent')->count();
            $hasFailed = $campaign->emails()->where('status', 'failed')->count();

            if ($hasSent) {
                $campaign->status = 'archived';
                $campaign->save();
            } else if (!$hasSent && !$hasFailed) {
                $campaign->status = 'purged';
                $campaign->save();
            }

        }
    }

    protected function handleFailedLog()
    {
        $campaignId = $this->campaignId;
        add_action('wp_mail_failed', function ($error) use ($campaignId) {
            $data = $error->get_error_data();
            $to = Arr::get($data, 'to');
            if ($to) {
                if (is_array($to)) {
                    $to = $to[0];
                }
            }

            if (!$to) {
                return;
            }

            CampaignEmail::where('campaign_id', $campaignId)
                ->where('email_address', $to)
                ->limit(1)
                ->orderBy('id', 'DESC')
                ->update([
                    'status' => 'failed',
                    'note'   => $error->get_error_message()
                ]);
        });
    }

    public function sendDoubleOptInEmail($subscriber)
    {
        if ($subscriber->status == 'subscribed' || !$subscriber->email) {
            return false; // already subscribed
        }

        $config = Helper::getDoubleOptinSettings();
        if (!Arr::get($config, 'email_subject') || !Arr::get($config, 'email_body')) {
            return false; // is not valid
        }

        $emailBody = apply_filters('fluentcrm_parse_campaign_email_text', $config['email_body'], $subscriber);
        $emailSubject = apply_filters('fluentcrm_parse_campaign_email_text', $config['email_subject'], $subscriber);
        $url = site_url('?fluentcrm=1&route=confirmation&s_id=' . $subscriber->id . '&hash=' . $subscriber->hash);

        $emailBody = apply_filters('fluentcrm_double_optin_email_body', $emailBody, $subscriber);
        $emailSubject = apply_filters('fluentcrm_double_optin_email_subject', $emailSubject, $subscriber);

        $emailBody = str_replace('#activate_link#', $url, $emailBody);

        $templateData = [
            'preHeader'   => '',
            'email_body'  => $emailBody,
            'footer_text' => '',
            'config'      => Helper::getTemplateConfig($config['design_template'], false)
        ];

        $emailBody = apply_filters(
            'fluentcrm_email-design-template-' . $config['design_template'],
            $emailBody,
            $templateData,
            false,
            $subscriber
        );

        if (strpos($emailBody, '##crm.') || strpos($emailBody, '{{crm.')) {
            // we have CRM specific smartcodes
            $emailBody = apply_filters('fluentcrm_parse_extended_crm_text', $emailBody, $subscriber);
        }

        $data = [
            'to'      => [
                'email' => $subscriber->email
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeader()
        ];

        Helper::maybeDisableEmojiOnEmail();
        Mailer::send($data);
        return true;
    }


    /**
     * Memory exceeded
     *
     * Ensures the batch process never exceeds 90% of the maximum WordPress memory.
     *
     * Based on WP_Background_Process::memory_exceeded()
     *
     * @return bool
     */
    protected function memory_exceeded()
    {
        $memory_limit = $this->get_memory_limit() * 0.70;
        $current_memory = memory_get_usage(true);

        $memory_exceeded = $current_memory >= $memory_limit;

        return apply_filters('fluentcrm_memory_exceeded', $memory_exceeded, $this);
    }

    /**
     * Get memory limit
     *
     * @return int
     */
    public function get_memory_limit()
    {
        if (function_exists('ini_get')) {
            $memory_limit = ini_get('memory_limit');
        } else {
            $memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
        }

        if (!$memory_limit || -1 === $memory_limit || '-1' === $memory_limit) {
            // Unlimited, set to 12GB.
            $memory_limit = '12G';
        }

        if (function_exists('wp_convert_hr_to_bytes')) {
            return wp_convert_hr_to_bytes($memory_limit);
        }

        $value = strtolower(trim($memory_limit));
        $bytes = (int)$value;

        if (false !== strpos($value, 'g')) {
            $bytes *= GB_IN_BYTES;
        } elseif (false !== strpos($value, 'm')) {
            $bytes *= MB_IN_BYTES;
        } elseif (false !== strpos($value, 'k')) {
            $bytes *= KB_IN_BYTES;
        }

        return min($bytes, PHP_INT_MAX);
    }

    private function callBackGround()
    {
        wp_remote_post(admin_url('admin-ajax.php'), [
            'sslverify' => false,
            'blocking'  => false,
            'body'      => [
                'campaign_id' => $this->campaignId,
                'retry'       => 1,
                'time'        => time(),
                'action'      => 'fluentcrm-post-campaigns-send-now'
            ]
        ]);
    }

    protected function updateEmailsStatus($ids, $status)
    {
        if (!$ids) {
            return false;
        }
        if ($status == 'sent') {
            fluentCrmDb()->table('fc_campaign_emails')
                ->whereIn('id', $ids)
                ->where('status', '!=', 'failed')
                ->update([
                    'email_body'   => '',
                    'updated_at'   => current_time('mysql'),
                    'scheduled_at' => current_time('mysql'),
                    'status'       => 'sent'
                ]);
        } else {
            fluentCrmDb()->table('fc_campaign_emails')
                ->whereIn('id', $ids)
                ->update([
                    'status'     => $status,
                    'updated_at' => current_time('mysql')
                ]);
        }
        return true;
    }
}
