<?php

namespace FluentCrm\Includes\Rest;

class Rest
{
    protected $app = null;

    protected $routes = [];

    protected $routeGroups = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function group($options = [], \Closure $callback = null)
    {
        if ($options instanceof \Closure) {
            $callback = $options;
            $options = [];
        }

        $this->routeGroups[] = $group = new Group(
            $this->app, $options, $callback
        );

        return $group;
    }

    public function get($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::READABLE
        );

        return $route;
    }

    public function post($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::CREATABLE
        );

        return $route;
    }

    public function put($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::EDITABLE
        );

        return $route;
    }

    public function patch($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::EDITABLE
        );

        return $route;
    }

    public function delete($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::DELETABLE
        );

        return $route;
    }

    public function any($path, $handler)
    {
        $this->routes[] = $route = $this->newRoute(
            $path, $handler, \WP_REST_Server::ALLMETHODS
        );

        return $route;
    }

    protected function newRoute($path, $handler, $method)
    {
        $path = trim($path, '/');

        $options = debug_backtrace(false, 4)[3]['args'];

        if ($options && count($options) > 1) {
            $options = $options[1];

            if (array_key_exists('prefix', $options)) {
                $prefix = $options['prefix'];
                $path = $prefix.'/'.$path;
            }

            if (array_key_exists('policy', $options)) {
                $policy = $options['policy'];
            }
        }

        $route = new Route(
            $this->app,
            $this->getPrefix(),
            $path,
            $handler,
            $method
        );

        if (isset($policy)) {
            $route->withPolicy($policy);
        }

        return $route;
    }

    protected function getPrefix()
    {
        $version = $this->app['rest.version'];
        $namespace = $this->app['rest.namespace'];
        return "{$namespace}/{$version}";
    }

    public function registerRoutes()
    {
        foreach ($this->routeGroups as $group) $group->register();

        foreach ($this->routes as $route) $route->register();
    }
}
