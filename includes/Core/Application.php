<?php

namespace FluentCrm\Includes\Core;

use ArrayAccess;
use FluentValidator\Validator;
use FluentCrm\Includes\Api\Api;
use FluentCrm\Includes\Core\App;
use FluentCrm\Includes\View\View;
use FluentCrm\Includes\Rest\Rest;
use FluentCrm\Includes\Core\Container;
use FluentCrm\Includes\Request\Request;
use FluentCrm\Includes\Response\Response;

final class Application extends Container implements ArrayAccess
{
    private $policyNamespace = 'FluentCrm\App\Http\Policies';

    private $handlerNamespace = 'FluentCrm\App\Hooks\Handlers';

    private $controllerNamespace = 'FluentCrm\App\Http\Controllers';

    public function addAction($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return add_action(
            $action,
            $this->getCallable($handler),
            $priority,
            $numOfArgs
        );
    }

    public function addCustomAction($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return $this->addAction($this->hook($action), $handler, $priority, $numOfArgs);
    }

    public function doAction()
    {
        return call_user_func_array('do_action', func_get_args());
    }

    public function doCustomAction()
    {
        $args = func_get_args();
        $args[0] = $this->hook($args[0]);
        return call_user_func_array('do_action', $args);
    }

    public function addFilter($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return add_filter(
            $action,
            $this->getCallable($handler),
            $priority,
            $numOfArgs
        );
    }

    public function addCustomFilter($action, $handler, $priority = 10, $numOfArgs = 1)
    {
        return $this->addFilter($this->hook($action), $handler, $priority, $numOfArgs);
    }

    public function applyFilters()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    public function applyCustomFilters()
    {
        $args = func_get_args();
        $args[0] = $this->hook($args[0]);
        return call_user_func_array('apply_filters', $args);
    }

    public function group($options = [], \Closure $callback = null)
    {
        return $this->rest->group($options, $callback);
    }

    public function get($route, $handler)
    {
        return $this->rest->get($route, $handler);
    }

    public function post($route, $handler)
    {
        return $this->rest->post($route, $handler);
    }

    public function put($route, $handler)
    {
        return $this->rest->put($route, $handler);
    }

    public function patch($route, $handler)
    {
        return $this->rest->patch($route, $handler);
    }

    public function delete($route, $handler)
    {
        return $this->rest->delete($route, $handler);
    }

    public function any($route, $handler)
    {
        return $this->rest->any($route, $handler);
    }

    public function hook($hook)
    {
        return FLUENTCRM . '-' . $hook;
    }

    private function getCallable($handler)
    {
        if (is_string($handler)) {
            list($class, $method) = preg_split('/::|@/', $handler);

            if ($this->hasNamespace($handler)) {
                $class = $this->make($class);
            } else {
                $class = $this->make($this->handlerNamespace . '\\' . $class);
            }
            return [$class, $method];

        } else if (is_array($handler)) {
            list($class, $method) = $handler;
            if (is_string($class)) {
                if ($this->hasNamespace($handler)) {
                    $class = $this->make($class);
                } else {
                    $class = $this->make($this->handlerNamespace . '\\' . $class);
                }
            }

            return [$class, $method];
        }

        return $handler;
    }

    public function hasNamespace($handler)
    {
        $parts = explode('\\', $handler);
        return count($parts) > 1;
    }

    public function parseRestHandler($handler)
    {
        if (!$handler) return;

        if ($this->hasNamespace($handler)) {
            return $handler;
        }

        if (is_string($handler)) {
            $handler = $this->controllerNamespace . '\\' . $handler;
        } else if (is_array($handler)) {
            list($class, $method) = $handler;
            if (is_string($class)) {
                $handler = $this->controllerNamespace . '\\' . $class . '::' . $method;
            }
        }

        return $handler;
    }

    public function parsePolicyHandler($handler)
    {
        if (!$handler) return;

        if (is_string($handler)) {
            if ($this->hasNamespace($handler)) {
                $handler = $handler;
            } else {
                $handler = $this->policyNamespace . '\\' . $handler;
            }

            if ($this->isCallableWithAtSign($handler)) {
                list($class, $method) = explode('@', $handler);
                if (!method_exists($class, $method)) {
                    $method = 'verifyRequest';
                    if (!method_exists($class, $method)) {
                        $method = '__returnTrue';
                    }
                }
                $instance = $this->make($class);
                $handler = [$instance, $method];
            }

        } else if (is_array($handler)) {
            list($class, $method) = $handler;

            if (is_string($class)) {
                if ($this->hasNamespace($handler)) {
                    $handler = $class . '::' . $method;
                } else {
                    $handler = $this->policyNamespace . '\\' . $class . '::' . $method;
                }
            }
        }

        return $handler;
    }

    private function registerPath($pluginPath)
    {
        $this['path'] = $pluginPath;
        $this['path.app'] = $pluginPath . 'app/';
        $this['path.views'] = $pluginPath . 'app/views/';
        $this['path.hooks'] = $pluginPath . 'app/Hooks/';
        $this['path.models'] = $pluginPath . 'app/models/';
        $this['path.includes'] = $pluginPath . 'includes/';
        $this['path.resources'] = $pluginPath . 'resources/';
        $this['path.controllers'] = $pluginPath . 'app/Http/controllers/';

        $this['path.admin.css'] = $pluginPath . 'resources/admin/css/';
        $this['path.admin.js'] = $pluginPath . 'resources/admin/js/';

        $this['path.public.css'] = $pluginPath . 'resources/public/css/';
        $this['path.public.js'] = $pluginPath . 'resources/public/js/';
    }

    private function registerURL($pluginUrl)
    {
        $this['url'] = $pluginUrl;
        $this['url.assets'] = $pluginUrl . 'assets/';
        $this['url.public.css'] = $pluginUrl . 'assets/public/css/';
        $this['url.admin.css'] = $pluginUrl . 'assets/admin/css/';
        $this['url.public.js'] = $pluginUrl . 'assets/public/js/';
        $this['url.admin.js'] = $pluginUrl . 'assets/admin/js/';
        $this['url.assets.images'] = $pluginUrl . 'assets/images/';
    }

    private function registerComponents($pluginPath)
    {
        $this->bind('FluentCrm\Includes\View\View', function ($app) {
            return new View($app);
        });

        $this->alias('FluentCrm\Includes\View\View', 'view');

        $this->singleton('FluentCrm\Includes\Request\Request', function ($app) {
            return new Request($app, $_GET, $_POST, $_FILES);
        });

        $this->alias('FluentCrm\Includes\Request\Request', 'request');

        $this->singleton('FluentCrm\Includes\Response\Response', function ($app) {
            return new Response($app);
        });

        $this->app->bind('FluentValidator\Validator', function($app) {
            return new Validator;
        });

        $this->alias('FluentValidator\Validator', 'validator');

        $this->alias('FluentCrm\Includes\Response\Response', 'response');

        $this->singleton('FluentCrm\Includes\Api\Api', function ($app) {
            return new Api($app);
        });
        
        $this->alias('FluentCrm\Includes\Api\Api', 'api');

        $this->singleton('FluentCrm\Includes\Rest\Rest', function ($app) {
            $app['rest.version'] = 'v2';
            $app['rest.namespace'] = 'fluent-crm';
            return new Rest($app);
        });

        $this->alias('FluentCrm\Includes\Rest\Rest', 'rest');
    }

    /**
     * Require all the common files that needs to be loaded on each request
     *
     * @param FluentCrm\App\Plugin $app [$app is being used inside required files]
     * @return void
     */
    private function requireCommonFilesForRequest($app)
    {
        // Require Global Functions
        require_once($this['path.app'] . '/Functions/helpers.php');

        // Require Action Hooks
        require_once($this['path.app'] . '/Hooks/actions.php');

        // Require Filter Hooks
        require_once($this['path.app'] . '/Hooks/filters.php');

        // Require Rest Routes
        require_once($this['path.app'] . '/Http/routes.php');

        // Init Rest API
        $this->addAction('rest_api_init', function ($wpRestServer) use ($app) {
            $app->rest->registerRoutes();
        });
    }

    public function __construct()
    {
        $this->setApplicationInstance($this);
        $this->registerURL(FLUENTCRM_PLUGIN_URL);
        $this->registerPath(FLUENTCRM_PLUGIN_PATH);
        $this->registerComponents(FLUENTCRM_PLUGIN_PATH);
        $this->requireCommonFilesForRequest($this);
        load_plugin_textdomain('fluentcrm', false, 'fluent-crm/language/');
    }

    private function setApplicationInstance($app)
    {
        App::setInstance($app);
        $this->instance('app', $app);
        $this->instance(__CLASS__, $app);
    }
}
