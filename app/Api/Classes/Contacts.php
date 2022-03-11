<?php
namespace FluentCrm\App\Api\Classes;

defined( 'ABSPATH' ) || exit;

use FluentCrm\App\Models\CustomContactField;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\ContactsQuery;
use FluentCrm\Framework\Support\Arr;

/**
 * Contacts Class - PHP APi Wrapper
 *
 * Contacts API Wrapper Class that can be used as <code>FluentCrmApi('contacts')</code> to get the class instance
 *
 * @package FluentCrm\App\Api\Classes
 * @namespace FluentCrm\App\Api\Classes
 *
 * @version 1.0.0
 */

class Contacts
{
    private $instance = null;

    private $allowedInstanceMethods = [
        'all',
        'get',
        'find',
        'first',
        'paginate'
    ];

    public function __construct(Subscriber $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Get Contact by contact id or email
     *
     * Use:
     * <code>FluentCrmApi('contacts')->getContact($idOrEmail);</code>
     *
     * @param int|string $idOrEmail Contact ID or Email
     * @return false|Subscriber Model of the subscriber
     */
    public function getContact($idOrEmail)
    {
        if (is_numeric($idOrEmail)) {
            return Subscriber::where('id', $idOrEmail)->first();
        } else if (is_string($idOrEmail)) {
            return Subscriber::where('email', $idOrEmail)->first();
        }
        return false;
    }

    /**
     * Get Contact by user id or Email
     *
     * Use:
     * <code>FluentCrmApi('contacts')->getContactByUserRef($userIdOrEmail);</code>
     *
     * @param int|string $userIdOrEmail User ID or Email
     * @return false|Subscriber Model of the subscriber
     */
    public function getContactByUserRef($userIdOrEmail)
    {
        $userIdFallback = false;
        if (is_numeric($userIdOrEmail)) {
            $subscriber = Subscriber::where('user_id', $userIdOrEmail)->first();
            if ($subscriber) {
                return $subscriber;
            }

            $user = get_user_by('ID', $userIdOrEmail);
            if (!$user) {
                return false;
            }
            $userIdFallback = $user->ID;
            $userIdOrEmail = $user->user_email;
        }

        $contact = false;

        if (is_string($userIdOrEmail)) {
            $contact = Subscriber::where('email', $userIdOrEmail)->first();
            if ($contact && $userIdFallback) {
                $contact->user_id = $userIdFallback;
                $contact->save();
            }
        }

        return $contact;
    }

    /**
     * Get Contact by contact id
     *
     * @param int $userId User ID
     * @return false|Subscriber Model of the subscriber
     */
    public function getContactByUserId($userId)
    {
        return Subscriber::where('user_id', $userId)->first();
    }


    /**
     * Create or Update Contact
     *
     * Usage:
     *
     * <code>FluentCrmApi('contacts')->createOrUpdateContact($data, $forceUpdate, $deleteOtherValues, $sync)</code>;
     *
     * @param array $data contact data to add or update
     * @param bool $forceUpdate if true, will update the contact status forcefully
     * @param bool $deleteOtherValues if true, will delete all custom fields data and add the new one
     * @param bool $sync no use case yet
     * @return false|Subscriber
     */
    public function createOrUpdate($data, $forceUpdate = false, $deleteOtherValues = false, $sync = false)
    {

        if (empty($data['email']) || !is_email($data['email'])) {
            return false;
        }

        if (!$forceUpdate) {
            $exist = Subscriber::where('email', $data['email'])->first();
            if ($exist && $exist->status != 'subscribed' && !empty($data['status'])) {
                $forceUpdate = true;
            }
        }

        if (!isset($data['custom_values'])) {
            $customFieldKeys = [];
            $customFields = (new CustomContactField)->getGlobalFields()['fields'];
            foreach ($customFields as $field) {
                $customFieldKeys[] = $field['slug'];
            }
            if ($customFieldKeys) {
                $customFieldsData = Arr::only($data, $customFieldKeys);
                $customFieldsData = array_filter($customFieldsData);
                if ($customFields) {
                    $data['custom_values'] = (new CustomContactField)->formatCustomFieldValues($customFieldsData);
                }
            }
        }

        return $this->instance->updateOrCreate($data, $forceUpdate, $deleteOtherValues, $sync);
    }


    /**
     * Get The current logged in contact
     *
     * Use
     * <pre>FluentCrmApi('contacts')->getCurrentContact()</pre>
     *
     *  @return false|Subscriber
     */
    public function getCurrentContact($cached = true)
    {
        static $currentContact;

        if ($cached && $currentContact) {
            return $currentContact;
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return false;
        }

        $user = get_user_by('ID', $userId);
        $currentContact = $this->instance->where('user_id', $user->ID)->orWhere('email', $user->user_email)->first();

        return $currentContact;
    }

    /**
     * To Contact's Advanced Query Class
     * Use
     * <pre>FluentCrmApi('contacts')->query($args)</pre>
     * @param $args array
     * @return \FluentCrm\App\Services\ContactsQuery
     */
    public function query($args)
    {
        return new ContactsQuery($args);
    }

    /**
     * @return \FluentCrm\App\Models\Subscriber
     */
    public function getInstance()
    {
        return $this->instance;
    }

    public function __call($method, $params)
    {
        if (in_array($method, $this->allowedInstanceMethods)) {
            return call_user_func_array([$this->instance, $method], $params);
        }

        throw new \Exception("Method {$method} does not exist.");
    }
}
