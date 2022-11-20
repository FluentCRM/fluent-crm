<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\App\Services\PermissionManager;
use FluentCrm\Framework\Foundation\Policy;
use FluentCrm\Framework\Request\Request;

/**
 *  BasePolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class BasePolicy extends Policy
{

    /**
     * Check user permission for any method
     * @param \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->currentUserCan('manage_options');
    }

    public function currentUserCan($permission)
    {
        return PermissionManager::currentUserCan($permission);
    }
}
