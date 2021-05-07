<?php

namespace FluentCrm\Includes\Core;

use FluentCrm\App\Services\PermissionManager;

abstract class Policy
{
    /**
     * Fallback method even if verifyRequest is not implemented.
     * @return bool true
     */
    public function __returnTrue()
    {
        return true;
    }

    public function currentUserCan($permission)
    {
        return PermissionManager::currentUserCan($permission);
    }

}
