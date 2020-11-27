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
        $permission = apply_filters('fluentcrm_permission', 'manage_options', 'tags', 'all');

        if (!$permission) {
            return false;
        }

        return current_user_can($permission);
    }

}
