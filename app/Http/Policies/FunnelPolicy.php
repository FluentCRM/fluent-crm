<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  FunnelPolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class FunnelPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if ($request->method() == 'GET') {
            return $this->currentUserCan('fcrm_read_funnels');
        }

        return $this->currentUserCan('fcrm_write_funnels');
    }

    public function delete(Request $request)
    {
        return $this->currentUserCan('fcrm_delete_funnels');
    }

    public function handleBulkAction(Request $request)
    {
        if ($request->get('action_name') == 'delete_funnels') {
            return $this->currentUserCan('fcrm_delete_funnels');
        }

        return $this->currentUserCan('fcrm_write_funnels');
    }
}
