<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;

class CliSendingHandler extends BaseHandler
{

    protected $runnerTitle = 'CliSendingHandler::handle';

    protected $sendingPerChunk = 30;

    protected $maximumProcessingTime = 50;

    public $offset = 350;

    public $minPendingRequired = 400;

    protected $optionKey = 'fluentcrm_is_sending_cli_emails';

    public function __construct($optionName = 'fluentcrm_is_sending_cli_emails', $runTime = 50, $offset = 350, $minPendingRequired = 400)
    {
        $this->optionKey = $optionName;
        $this->maximumProcessingTime = $runTime;
        $this->offset = $offset;
        $this->minPendingRequired = $minPendingRequired;
    }

    public function handle()
    {
        $systemCheck = $this->isSystemOk();
        if (is_wp_error($systemCheck)) {
            return $systemCheck;
        }

        Helper::maybeDisableEmojiOnEmail();
        Helper::debugLog('Starting ' . $this->runnerTitle, '', 'extended');

        try {
            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            $result = $this->processBatchEmails();

            if (is_wp_error($result)) {
                update_option($this->optionKey, null);
                $this->logSentCount();
                return new \WP_Error('wp_error', $result->get_error_message());
            }

            if ($result === 'time_up') {
                $this->logSentCount();
                return new \WP_Error('time_up', 'Time Up');
            }

        } catch (\Exception $e) {
            Helper::debugLog('Exception at ' . $this->runnerTitle, $e->getMessage(), 'error');
            return new \WP_Error('exception', $e->getMessage());
        }

        $this->logSentCount();

        update_option($this->optionKey, null);
        return true;
    }

    private function isSystemOk()
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return new \WP_Error('not_cli', 'This is not a CLI request');
        }

        $this->calledFrom = 'CLI';

        if (
            did_action('fluent_crm/sending_cli_threading_email') ||
            apply_filters('fluent_crm/disable_email_processing', false)
        ) {
            return new \WP_Error('disabled', 'Email Processing is disabled');
        }

        if ($this->memoryExceeded()) {
            Helper::debugLog('Mailer Memory Exceeded at ' . $this->runnerTitle, 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true));
            return new \WP_Error('memory_exceeded', 'Memory Exceeded at ' . $this->runnerTitle);
        }

        if (Helper::getUpcomingEmailCount() < $this->minPendingRequired) {
            return new \WP_Error('not_enough', 'Pending emails are not enough to process');
        }

        $this->isMultiThread = true;
        $this->startingTimeStamp = time();

        if ($this->isProcessing()) {
            Helper::debugLog('already Processing', 'Handler::handle', 'extended');
            return new \WP_Error('already_processing', 'Already Processing');
        }

        return true;
    }

    protected function getNextBatchEmails()
    {
        $remaining = Helper::getUpcomingEmailCount();
        if ($remaining < $this->minPendingRequired) {
            \WP_CLI::line(sprintf('only %d emails left. Exiting....', $remaining));
            return [];
        }

        if ($this->memoryExceeded()) {
            Helper::debugLog('Mailer Memory Exceeded at ' . $this->runnerTitle, 'Memory Limit: ' . fluentCrmGetMemoryLimit() . '<br />Current Usage: ' . memory_get_usage(true));
            return [];
        }

        if ($this->sentCount) {
            \WP_CLI::line(sprintf('Sent %1d emails. -> %2d', $this->sentCount, $this->sendingChunkNumber));
        }

        $currentTime = current_time('mysql');
        $emails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', $currentTime)
            ->with('campaign', 'subscriber')
            ->orderBy('scheduled_at', 'DESC')
            ->offset($this->offset)
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

    public function setRunnerTitle($title)
    {
        $this->runnerTitle = $title;
        return $this;
    }

    protected function isTimeUp()
    {
        return (time() - $this->startingTimeStamp) >= $this->maximumProcessingTime;
    }
}
