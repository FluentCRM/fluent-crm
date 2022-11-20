<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  ListPolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */

class ListPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param  \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contact_cats');
    }


    /**
     * Check user permission for delete lists
     * @param  \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function remove(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contact_cats_delete');
    }

    public function handleBulkAction(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contact_cats_delete');
    }
}
