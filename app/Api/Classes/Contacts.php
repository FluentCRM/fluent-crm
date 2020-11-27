<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\CustomContactField;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Includes\Helpers\Arr;

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

    public function getContact($idOrEmail)
    {
        if(is_numeric($idOrEmail)) {
            return Subscriber::where('id', $idOrEmail)->first();
        } else if(is_string($idOrEmail)) {
            return Subscriber::where('email', $idOrEmail)->first();
        }
        return false;
    }

    public function getContactByUserRef($userIdOrEmail)
    {
        if(is_numeric($userIdOrEmail)) {
            return Subscriber::where('user_id', $userIdOrEmail)->first();
        } else if(is_string($userIdOrEmail)) {
            return Subscriber::where('email', $userIdOrEmail)->first();
        }
        return false;
    }

    public function getContactByUserId($userId)
    {
        return Subscriber::where('user_id', $userId)->first();
    }

    public function createOrUpdate($data, $forceUpdate = false, $deleteOtherValues = false, $sync = false)
    {

        if(empty($data['email']) || !is_email($data['email'])) {
            return false;
        }

        if(!$forceUpdate) {
            $exist = Subscriber::where('email', $data['email'])->first();
            if($exist && $exist->status != 'subscribed' && !empty($data['status'])) {
                $forceUpdate = true;
            }
        }

        if(!isset($data['custom_values'])) {
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

    public function getCurrentContact()
    {
        $userId = get_current_user_id();
        if(!$userId) {
            return false;
        }

        $user = get_user_by('ID', $userId);
        return $this->instance->where('user_id', $user->ID)->orWhere('email', $user->user_email)->first();
    }

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
