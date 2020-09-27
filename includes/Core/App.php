<?php

namespace FluentCrm\Includes\Core;

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
}
