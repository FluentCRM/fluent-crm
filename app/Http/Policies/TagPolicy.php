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
        return current_user_can('manage_options');
    }

    /**
     * Check user permission for store method
     * @param  \FluentCrm\Includes\Request\Request $request
     * @return Boolean
     */
    public function store(Request $request)
    {
        return current_user_can('manage_options');
    }
}
