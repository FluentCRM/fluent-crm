<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Includes\Core\Policy;
use FluentCrm\Includes\Request\Request;

class FunnelPolicy extends Policy
{
    /**
     * Check user permission for any method
     * @param  \FluentCrm\Includes\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if($request->method() == 'GET') {
            return $this->currentUserCan('fcrm_read_funnels');
        }

        return $this->currentUserCan('fcrm_write_funnels');
    }
}
