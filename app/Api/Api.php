<?php

namespace FluentCrm\App\Api;

/**
 * Internal PHP API Class
 *
 * Please do not use this class directly. use FluentCrmApi($module) instead.
 *
 * @package FluentCrm\App\Api\Classes
 *
 * @version 1.0.0
 */

final class Api
{

    public $app;

    public function __construct($app)
    {
        $this->app = $app;
        $this->register();
    }

    private function register()
    {
        foreach ($this->getClasses() as $key => $class) {
            $this->app->singleton($this->key($key), function($app) use ($class) {
                return new FCApi($app->make($class));
            });
        }
    }

    private function getClasses()
    {
        return require_once(
            $this->app['path.app'].'Api/config.php'
        );
    }

    private function key($key)
    {
        return '__fluentcrm_api__.' . $key;
    }

    public function __get($key)
    {
        try {
            return $this->app[$this->key($key)];
        } catch(\Exception $e) {
            throw new \Exception("The '$key' doesn't exist in FluentCrmApi.");
        }
    }
}
