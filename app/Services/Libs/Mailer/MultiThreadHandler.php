<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class MultiThreadHandler extends BaseHandler
{

    protected $runnerTitle = 'MultiThreadHandler::handle';

    protected $sendingPerChunk = 20;

    protected $maximumProcessingTime = 50;

    protected $optionKey = 'fluentcrm_is_sending_multi_emails';

    public function handle()
    {
        if (!$this->isSystemOk()) {
            return true; // Early return
        }

        Helper::maybeDisableEmojiOnEmail();

        try {
            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            $result = $this->processBatchEmails();

            if (is_wp_error($result)) {
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
            Helper::debugLog('Exception at ' . $this->runnerTitle, $e->getMessage(), 'error');
        }

        $this->logSentCount();

        update_option($this->optionKey, null);
        return true;
    }

    private function isSystemOk()
    {
        $this->calledFrom = Arr::get($_REQUEST, 'action') == 'fluentcrm-post-multi-thread-send-now' ? 'ajax' : 'cron';

        if ($this->calledFrom == 'cron') {
            fluentcrm_update_option($this->optionKey . '_last_called', time());
        }

        if (
            did_action('fluent_crm/sending_multi_threading_email') ||
            apply_filters('fluent_crm/disable_email_processing', false)
        ) {
            return false;
        }

        if ($this->memoryExceeded()) {
            Helper::debugLog('Mailer Memory Exceeded at ' . $this->runnerTitle, 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true));
            return false;
        }

        if (!Helper::willMultiThreadEmail(300)) {
            as_schedule_single_action(time() + 1, 'fluent_crm_cancel_multi_thread_mailing', [], 'fluent-crm', true);
            return false;
        }

        $this->isMultiThread = true;
        $this->startingTimeStamp = time();

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
            ->offset(250)
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

    protected function isTimeUp()
    {
        return (time() - $this->startingTimeStamp) >= $this->maximumProcessingTime;
    }

    private function callBackGround()
    {
        if ($this->memoryExceeded()) {
            Helper::debugLog('Memory Exceeded at MultiThreadHandler::callBackGround', 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true));
            return;
        }

        $nextCron = as_next_scheduled_action('fluent_crm_send_multi_thread_emails');
        $willRun = !$nextCron || $nextCron == 1 || ($nextCron - time()) >= 5 || ($nextCron - time()) < -70;


        if (!$willRun) {
            $lastCalled = (int)fluentcrm_get_option($this->optionKey . '_last_called');
            if ($lastCalled && (time() - $lastCalled) < 50) {
                $willRun = true;
            }
        }

        if ($willRun) { // If next cron is after more than 5 seconds we want to run this or it's running
            wp_remote_post(admin_url('admin-ajax.php'), [
                'sslverify' => false,
                'blocking'  => false,
                'body'      => [
                    'campaign_id' => null,
                    'retry'       => 1,
                    'time'        => time(),
                    'action'      => 'fluentcrm-post-multi-thread-send-now'
                ]
            ]);
        }
    }
}
