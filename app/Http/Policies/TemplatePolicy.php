<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  TemplatePolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */

class TemplatePolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param  \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_templates');
    }

    public function delete(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }

    public function handleBulkAction(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }
}
