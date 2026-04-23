<?php


namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

abstract class BaseHandler
{
    protected $startedAt = 0;

    protected $runnerTitle = '';

    protected $sentCount = 0;

    protected $maximumProcessingTime = 50;

    protected $calledFrom = 'cron';

    protected $startingTimeStamp = null;

    protected $optionKey = 'fluentcrm_is_sending_emails';

    protected $isMultiThread = false;

    protected $dispatchedWithinOneSecond = 0;

    protected $emailLimitPerSecond = 0;

    protected $sendingChunkNumber = 0;

    abstract protected function isTimeUp();

    protected function sendEmails($campaignEmails)
    {
        global $wpdb;
        do_action('fluent_crm/sending_emails_starting', $campaignEmails);

        if (defined('FLUENTMAIL')) {
            add_filter('fluentmail_will_log_email', 'fluentcrm_maybe_disable_fsmtp_log', 10, 2);
        }

        $failedIds = [];

        $this->sendingChunkNumber++;

        $sendableStatuses = ['subscribed', 'transactional'];

        foreach ($campaignEmails as $email) {
            if ($this->reachedEmailLimitPerSecond()) {
                $this->updateEmailsStatus($failedIds, 'failed');
                $failedIds = [];
                $this->restartWhenOneSecondExceeds();
            }

            // Check again if the contact is in subscribed status or not
            // If not then we will cancel the email
            if ($email->subscriber && !in_array($email->subscriber->status, $sendableStatuses, true)) {
                $email->status = 'cancelled';
                $email->save();
                continue;
            }

            $emailData = $email->data();
            try {
                $wpdb->update(
                    $wpdb->prefix . 'fc_campaign_emails',
                    [
                        'status'       => 'sent',
                        'scheduled_at' => current_time('mysql'),
                        'email_body'   => ''
                    ],
                    [
                        'id' => $email->id
                    ]
                );
                if ($wpdb->last_error) {
                    Helper::debugLog('DB Error at ' . $this->runnerTitle, $wpdb->last_error, 'error');
                    return new \WP_Error('db_error', $wpdb->last_error);
                }
            } catch (\Exception $e) {
                Helper::debugLog('DB Error (Exception) at ' . $this->runnerTitle, $e->getMessage(), 'error');
                return new \WP_Error('db_error', $e->getMessage());
            }

            $this->sentCount++;

            $response = Mailer::send($emailData, $email->subscriber, $email);

            $this->dispatchedWithinOneSecond++;

            if (is_wp_error($response)) {
                $failedIds[] = $email->id;
            }
        }

        $this->updateEmailsStatus($failedIds, 'failed');

        if (defined('FLUENTMAIL')) {
            remove_filter('fluentmail_will_log_email', 'fluentcrm_maybe_disable_fsmtp_log', 10);
        }

        do_action('fluentcrm_sending_emails_done', $campaignEmails);

        return true;
    }

    protected function processBatchEmails()
    {
        if ($this->isTimeUp()) {
            update_option($this->optionKey, null);
            return 'time_up';
        }

        $emails = $this->getNextBatchEmails();

        if (!$emails || $emails->isEmpty()) {
            update_option($this->optionKey, null);
            return 'empty';
        }

        $this->processing();
        $result = $this->sendEmails($emails);

        if (is_wp_error($result)) {
            return $result;
        }

        usleep(10000); // 0.01 seconds sleep

        return $this->processBatchEmails();
    }

    abstract protected function getNextBatchEmails();

    protected function logSentCount()
    {
        if ($this->sentCount) {
            Helper::debugLog(sprintf($this->runnerTitle . ': Sent %d', $this->sentCount), sprintf('%d seconds via %s', time() - $this->startingTimeStamp, $this->calledFrom));
        }
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
    protected function memoryExceeded()
    {
        $memory_limit = fluentCrmGetMemoryLimit() * 0.70;
        $current_memory = memory_get_usage(true);

        $memory_exceeded = $current_memory >= $memory_limit;

        return apply_filters('fluentcrm_memory_exceeded', $memory_exceeded, $this);
    }

    protected function reachedEmailLimitPerSecond()
    {
        $emailLimitPerSecond = $this->getEmailLimitPerSecond();
        return ($emailLimitPerSecond && $this->dispatchedWithinOneSecond >= $emailLimitPerSecond);
    }

    protected function restartWhenOneSecondExceeds()
    {
        $elapsedTimeMicroSeconds = (microtime(true) - $this->startedAt) * 1000000;
        $remainingTimeMicroSeconds = 1000000 - $elapsedTimeMicroSeconds;

        if ($remainingTimeMicroSeconds > 0) {
            usleep((int)ceil($remainingTimeMicroSeconds));
            $seconds = number_format($remainingTimeMicroSeconds / 1000000, 4);
            Helper::debugLog('Restarting ' . $this->runnerTitle, 'Halt For: ' . $seconds . ' Seconds ' . $this->emailLimitPerSecond, 'info');
        }

        $this->dispatchedWithinOneSecond = 0;
        $this->startedAt = microtime(true);
    }

    protected function updateEmailsStatus($ids, $status)
    {
        if (!$ids) {
            return false;
        }

        fluentCrmDb()->table('fc_campaign_emails')
            ->whereIn('id', $ids)
            ->update([
                'status' => $status
            ]);

        return true;
    }

    protected function handleFailedLog()
    {
        add_action('wp_mail_failed', function ($error) {
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

            CampaignEmail::where('email_address', $to)
                ->limit(1)
                ->orderBy('id', 'DESC')
                ->update([
                    'status' => 'failed',
                    'note'   => $error->get_error_message()
                ]);
        });
    }

    protected function getEmailLimitPerSecond()
    {
        if ($this->emailLimitPerSecond) {
            return $this->emailLimitPerSecond;
        }

        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        if (!empty($emailSettings['emails_per_second'])) {
            $limit = (int)$emailSettings['emails_per_second'] - 3; // 3 is buffer
        } else {
            $limit = 14;
        }

        if (!$limit || $limit < 4) {
            $limit = 4;
        }

        if ($this->isMultiThread) {
            $limit = ceil($limit / 2);
        }

        $limit = apply_filters('fluent_crm/email_limit_per_second', $limit, $emailSettings, $this);

        $this->emailLimitPerSecond = $limit;

        return $this->emailLimitPerSecond;
    }

    protected function isProcessing()
    {
        $lastProcessStartedAt = get_option($this->optionKey);

        if (!$lastProcessStartedAt) {
            return false;
        }

        if ($this->seemsStuck($lastProcessStartedAt)) {
            return false;
        }

        return true;
    }

    protected function seemsStuck($lastProcessStartedAt)
    {
        if ($lastProcessStartedAt && time() - $lastProcessStartedAt > 80) {
            $this->processing();
            return true;
        }

        return false;
    }

    protected function processing()
    {
        update_option($this->optionKey, time());
    }
}
