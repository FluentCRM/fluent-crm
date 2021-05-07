<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Includes\Core\Policy;
use FluentCrm\Includes\Request\Request;

class TagPolicy extends Policy
{
    /**
     * Check user permission for index method
     * @param  \FluentCrm\Includes\Request\Request $request
     * @return Boolean
     */
    public function index(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contact_cats');
    }

}
