<?php

namespace FluentCrm\Framework\Foundation;


class App
{   
    protected static $instance = null;

    public static function setInstance($app)
    {
        static::$instance = $app;
    }

    public static function getInstance($module = null)
    {
        if ($module) {
            return static::$instance[$module];
        }

        return static::$instance;
    }

    public static function make($module = null)
    {
        return static::getInstance($module);
    }

    public static function __callStatic($method, $params)
    {
        if ($method == 'user') {
            return static::$instance->user();
        }

        return static::getInstance($method);
    }
}
