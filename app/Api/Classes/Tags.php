<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\models\Tag;

class Tags
{
    private $instance = null;

    private $allowedInstanceMethods = [
        'all',
        'get',
        'find',
        'first',
        'paginate'
    ];

    public function __construct(Tag $instance)
    {
        $this->instance = $instance;
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
