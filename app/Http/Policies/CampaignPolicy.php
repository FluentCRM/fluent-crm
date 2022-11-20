<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  CampaignPolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class CampaignPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if ($request->method() == 'GET') {
            return $this->currentUserCan('fcrm_read_emails');
        }

        return $this->currentUserCan('fcrm_manage_emails');
    }

    public function delete(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }

    public function deleteCampaignEmails(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }

    public function handleBulkAction(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }
}
