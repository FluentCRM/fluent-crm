<?php

namespace FluentCrm\Includes\Parser;

class Parser
{
    public function __call($method, $params)
    {
        $instance = new ShortcodeParser;
        return call_user_func_array([$instance, $method], $params);
    }

    public static function __callStatic($method, $params)
    {
        $instance = new static;
        return call_user_func_array([$instance, $method], $params);
    }
}
