<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  ImportUserPolicy - Import Contact Policy
 *
 * @package FluentCrm\App\Http
 */
class ImportUserPolicy extends BasePolicy {
    /**
     * Check user permission for any method
     *
     * @param \FluentCrm\Framework\Request\Request $request
     *
     * @return Boolean
     */
    public function verifyRequest( Request $request )
    {
        return $this->currentUserCan('fcrm_manage_contacts');
    }
}
