<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\models\Subscriber;

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

    public function getContactByUserId($userId)
    {
        return Subscriber::where('user_id', $userId)->first();
    }

    public function createOrUpdate($data)
    {
        return $this->instance->updateOrCreate($data);
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
