<?php

namespace WPManageNinja\WPOrm;

class QueryBuilder
{
    protected $query = null;

    public function __construct()
    {
        $this->query = wpFluent();
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->query, $method], $params);
    }
}
