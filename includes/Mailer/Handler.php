<?php

namespace FluentCrm\Includes\Mailer;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;

class Handler
{
    protected $startedAt = null;

    protected $campaignId = false;

    protected $emailLimitPerSecond = 0;

    protected $maximumProcessingTime = 50;

    protected $dispatchedWithinOneSecond = 0;

    public function handle($campaignId = null)
    {
        if( did_action('fluentcrm_sending_emails_starting') ) {
            return false;
        }

        if ( apply_filters('fluentcrm_disable_email_processing', false) ) {
            return false;
        }

        $this->maximumProcessingTime = apply_filters('fluentcrm_max_email_sending_time', 10);

        $sendingPerChunk = apply_filters('fluentcrm_email_sending_per_chunk', 7);

        $hadJobs = false;

        try {
            $this->campaignId = $campaignId;
            if ($this->isProcessing()) {
                return false;
            }

            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            $startedTimeStamp = time();

            foreach ((new CampaignEmailIterator($campaignId, $sendingPerChunk)) as $emailCollection) {
                $hadJobs = true;
                if ((time() - $startedTimeStamp) > $this->maximumProcessingTime) {
                    update_option(FLUENTCRM . '_is_sending_emails', null);
                    if(!$this->memory_exceeded()) {
                        $this->callBackGround();
                    }
                    return false; // we don't want to run the process for more than 50 seconds
                }
                $this->updateProcessTime();
                $this->sendEmails($emailCollection);
            }
        } catch (\Exception $e) {

        }

        update_option(FLUENTCRM . '_is_sending_emails', null);

        if ($hadJobs || mt_rand (1, 50) > 25 ) { // sometimes we want to check this
            CampaignEmail::where('status', 'processing')
                ->where('updated_at', '<', date('Y-m-d H:i:s', (time() - $this->maximumProcessingTime - 5)))
                ->update([
                    'status' => 'pending'
                ]);
        }

        if ($hadJobs && !$this->memory_exceeded()) {
            $this->callBackGround();
            return false;
        }

        if (!$hadJobs && mt_rand(1, 50) > 20) {
            do_action('fluentcrm_scheduled_maybe_regular_tasks');
        }

    }

    public function processSubscriberEmail($subscriberId)
    {
        $emailCollection = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', current_time('mysql'))
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->where('subscriber_id', $subscriberId)
            ->get();

        $ids = $emailCollection->pluck('id');
        if($ids) {
            CampaignEmail::whereIn('id', $ids)
                ->update([
                    'status' => 'processing',
                    'updated_at' => date('Y-m-d H:i:s')
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

        Campaign::where('status', 'pending')
            ->when($this->campaignId, function ($query) {
                $query->where('id', $this->campaignId);
            })
            ->where('scheduled_at', '<=', fluentCrmUTCTimestamp())
            ->update(['status' => 'working']);

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

        if (defined('FLUENTSMTP')) {
            add_filter('fluentmail_will_log_email', 'fluentcrm_maybe_disable_fsmtp_log', 10, 2);
        }

        $failedIds = [];
        $sentIds = [];
        foreach ($campaignEmails as $email) {
            if ($this->reachedEmailLimitPerSecond()) {
                $this->restartWhenOneSecondExceeds();
            } else {
                $response = Mailer::send($email->data());
                $this->dispatchedWithinOneSecond++;
                if (is_wp_error($response)) {
                    $failedIds[] = $email->id;
                } else {
                    CampaignEmail::where('id', $email->id)->whereNot('status', 'failed')->update([
                        'status'     => 'sent',
                        'updated_at' => current_time('mysql')
                    ]);
                    $sentIds[] = $email->id;
                }
            }
        }

        if ($sentIds) {
            CampaignEmail::whereIn('id', $sentIds)
                ->where('campaign_id', '>=', 1)
                ->whereNot('status', 'failed')
                ->update([
                    'email_body' => ''
                ]);
        }

        if ($failedIds) {
            CampaignEmail::whereIn('id', $failedIds)->update([
                'status' => 'failed'
            ]);
        }

        if (defined('FLUENTSMTP')) {
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
        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        if (!empty($emailSettings['emails_per_second'])) {
            $limit = intval($emailSettings['emails_per_second']);
        } else {
            $limit = 14;
        }

        if (!$limit || $limit < 2) {
            $limit = 2;
        }


        return ($limit > $this->emailLimitPerSecond) ? ($limit - 1) : $limit;
    }

    public function finishProcessing()
    {
        $this->markArchiveCampaigns();
        $this->jobCompleted();
    }

    protected function markArchiveCampaigns()
    {
        $campaigns = Campaign::where('status', 'working')->whereDoesNotHave('emails', function ($query) {
            $query->whereIn('status', ['pending', 'failed', 'scheduled', 'processing']);
        })->get();

        if (!$campaigns->isEmpty()) {
            Campaign::whereIn(
                'id', array_unique($campaigns->pluck('id'))
            )->update(['status' => 'archived']);
        }
    }

    protected function jobCompleted()
    {
        // If we've still some campaigns in working mode then thay are stuck so
        // Mark those campaigns and their pending emails as purged, so we can show
        // those campaigns in the campaign's page (index) allowed to edit the campaign.
        foreach (Campaign::where('status', 'working')->get() as $campaign) {

            $hasPending = $campaign->emails()->where('status', 'pending')->count();
            if ($hasPending) {
                continue;
            }

            $hasSent = $campaign->emails()->where('status', 'sent')->count();
            $hasFailed = $campaign->emails()->where('status', 'failed')->count();

            if (!$hasPending) {
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
        if ($subscriber->status == 'subscribed') {
            return false; // already subscribed
        }
        $config = Helper::getDoubleOptinSettings();
        if (!Arr::get($config, 'email_subject') || !Arr::get($config, 'email_body')) {
            return false; // is not valid
        }

        $emailBody = apply_filters('fluentcrm-parse_campaign_email_text', $config['email_body'], $subscriber);
        $emailSubject = apply_filters('fluentcrm-parse_campaign_email_text', $config['email_subject'], $subscriber);
        $url = site_url('?fluentcrm=1&route=confirmation&s_id=' . $subscriber->id . '&hash=' . $subscriber->hash);
        $emailBody = str_replace('#activate_link#', $url, $emailBody);

        $templateData = [
            'preHeader'   => '',
            'email_body'  => $emailBody,
            'footer_text' => '',
            'config'      => Helper::getTemplateConfig($config['design_template'])
        ];

        $emailBody = apply_filters(
            'fluentcrm-email-design-template-' . $config['design_template'],
            $emailBody,
            $templateData,
            false,
            $subscriber
        );

        $data = [
            'to'      => [
                'email' => $subscriber->email
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeader()
        ];
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
        $memory_limit = $this->get_memory_limit() * 0.90;
        $current_memory = memory_get_usage(true);

        $memory_exceeded = $current_memory >= $memory_limit;

        return apply_filters('fluentcrm_memory_exceeded', $memory_exceeded, $this);
    }

    /**
     * Get memory limit
     *
     * @return int
     */
    protected function get_memory_limit()
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
                'time' => time(),
                'action'      => 'fluentcrm-post-campaigns-send-now'
            ]
        ]);
    }
}
