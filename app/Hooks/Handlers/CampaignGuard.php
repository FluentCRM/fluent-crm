<?php

namespace FluentCrm\App\Hooks\Handlers;

/**
 *  CampaignGuard Class
 *
 * Used to handle concurrent requests for the same campaign.
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */

class CampaignGuard
{
    const FORBIDDEN_CODE = 403;

    public function checkIsActive($campaign)
    {
        if (!$campaign) {
            $this->send('The campaign is not available anymore.');
        }

        $status = $campaign->status;

        if (!in_array($status, ['draft', 'pending', 'incomplete', 'purged', 'scheduled'])) {
            $message = __('The campaign has been locked and not modifiable due to it\'s current status', 'fluent-crm');
            $message .= ": <strong>{$status}</strong>.";
            $this->send($message);
        }

        return;
    }

    public function checkIsWorking($campaign)
    {
        if (!$campaign) {
            $this->send('The campaign is not available anymore.');
        }

        $status = $campaign->status;

        if ($status == 'working') {
            $message = __("The campaign has been locked and not deletable due to it's current status", "fluent-crm");
            $message .= ": <strong>{$status}</strong>.";
            $this->send($message);
        }

        return;
    }

    protected function send($message)
    {
        FluentCrm('response')->sendError([
            'status' => self::FORBIDDEN_CODE,
            'message' => "<p style='font-weight:500;color:#606266;'>{$message}</p>"
        ], self::FORBIDDEN_CODE);
    }
}
