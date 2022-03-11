<?php

namespace FluentCrm\Framework\Foundation;

use FluentCrm\Framework\Support\Arr;

class Config
{
    protected $data = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function all()
    {
        return $this->get();
    }

    public function get($key = null, $default = null)
    {
        return $key ? Arr::get($this->data, $key, $default) : $this->data;
    }

    public function set($key, $value)
    {
        Arr::set($this->data, $key, $value);
    }
}
