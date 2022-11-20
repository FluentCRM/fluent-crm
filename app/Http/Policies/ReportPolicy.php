<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  ReportPolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */

class ReportPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param  \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->currentUserCan('fcrm_view_dashboard');
    }

    public function getEmails(Request $request)
    {
        return $this->currentUserCan('fcrm_read_emails');
    }

    public function deleteEmails(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }
}
