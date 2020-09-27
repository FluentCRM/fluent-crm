<?php

namespace WPManageNinja\WPOrm;

use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Paginator implements JsonSerializable, IteratorAggregate
{
    protected $result = null;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function jsonSerialize()
    {
        return $this->result;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->result['data']->all());
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->result['data'], $method], $params);
    }
}
