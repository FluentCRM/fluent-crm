<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\App\Services\PermissionManager;
use FluentCrm\Framework\Foundation\Policy;

/**
 *  BasePolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class BasePolicy extends Policy
{
    public function currentUserCan($permission)
    {
        return PermissionManager::currentUserCan($permission);
    }
}
