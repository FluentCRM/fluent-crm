<?php

namespace FluentCrm\App\Http\Policies;

use FluentCrm\Framework\Request\Request;

/**
 *  SubscriberPolicy - REST API Permission Policy
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class SubscriberPolicy extends BasePolicy
{
    /**
     * Check user permission for any method
     * @param \FluentCrm\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if ($request->method() == 'GET') {
            return $this->currentUserCan('fcrm_read_contacts');
        }

        return $this->currentUserCan('fcrm_manage_contacts');
    }

    public function deleteSubscriber(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contacts_delete');
    }

    public function deleteSubscribers(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contacts_delete');
    }

    public function deleteNote(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_contacts_delete');
    }

    public function deleteEmails(Request $request)
    {
        return $this->currentUserCan('fcrm_manage_email_delete');
    }

    public function handleBulkActions(Request $request)
    {
        $actionName = $request->get('action_name');

        if (!$actionName) {
            return $this->currentUserCan('fcrm_manage_contacts');
        }


        $actionMaps = [
            'add_to_email_sequence' => 'fcrm_manage_emails',
            'add_to_automation'     => 'fcrm_write_funnels',
            'delete_contacts'       => 'fcrm_manage_contacts_delete'
        ];

        if (isset($actionMaps[$actionName])) {
            return $this->currentUserCan($actionMaps[$actionName]);
        }

        return $this->currentUserCan('fcrm_manage_contacts');
    }
}
