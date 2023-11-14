<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\App\Http\Policies\BasePolicy;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Request\Request;

class CompanyPolicy extends BasePolicy
{
    private function isEnabled()
    {
        return Helper::isCompanyEnabled();
    }

    /**
     * Check user permission for any method
     * @param  \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->isEnabled() && $this->currentUserCan('fcrm_manage_contact_cats');
    }

    public function delete(Request $request)
    {
        return $this->isEnabled() && $this->currentUserCan('fcrm_manage_contact_cats_delete');
    }

    public function deleteSubscribes(Request $request)
    {
        return $this->isEnabled() && $this->currentUserCan('fcrm_manage_contact_cats_delete');
    }
}
