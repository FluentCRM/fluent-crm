<?php

namespace FluentCrm\Includes\Api;

final class FCApi
{
    private $instance = null;

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    public function __call($method, $params)
    {
        try {
            $result = call_user_func_array([$this->instance, $method], $params);

            if (is_object($result)) {
                return $result;
            }

            throw new \Exception("Error Processing Request");

        } catch (\Exception $e) {
            return $result;
        }
    }
}
