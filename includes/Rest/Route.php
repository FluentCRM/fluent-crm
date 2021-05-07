<?php

namespace FluentCrm\Includes\Rest;

class Route
{
    protected $app = null;

    protected $prefix = null;

    protected $path = null;

    protected $handler = null;

    protected $method = null;

    protected $wheres = [];

    protected $policyHandler = null;

    protected $predefinedNamedRegx = [
        'int' => '[0-9]+',
        'alpha' => '[a-zA-Z]+',
        'alpha_num' => '[a-zA-Z0-9]+',
        'alpha_num_dash' => '[a-zA-Z0-9-_]+'
    ];


    public function __construct($app, $prefix, $path, $handler, $method)
    {
        $this->app = $app;
        $this->prefix = $prefix;
        $this->path = $path;
        $this->handler = $handler;
        $this->method = $method;
    }

    public function where($identifier, $value = null)
    {
        if (!is_null($value)) {
            $this->wheres[$identifier] = $this->getValue($value);
        } else {
            foreach ($identifier as $key => $value) {
                $this->wheres[$key] = $this->getValue($value);
            }
        }

        return $this;
    }

    public function int($identifiers)
    {
        $identifiers = is_array($identifiers) ? $identifiers : func_get_args();

        foreach ($identifiers as $identifier) {
            $this->wheres[$identifier] = '[0-9]+';
        }

        return $this;
    }

    public function alpha($identifiers)
    {
        $identifiers = is_array($identifiers) ? $identifiers : func_get_args();

        foreach ($identifiers as $identifier) {
            $this->wheres[$identifier] = '[a-zA-Z]+';
        }

        return $this;
    }

    public function alphaNum($identifiers)
    {
        $identifiers = is_array($identifiers) ? $identifiers : func_get_args();

        foreach ($identifiers as $identifier) {
            $this->wheres[$identifier] = '[a-zA-Z0-9]+';
        }

        return $this;
    }

    public function alphaNumDash($identifiers)
    {
        $identifiers = is_array($identifiers) ? $identifiers : func_get_args();

        foreach ($identifiers as $identifier) {
            $this->wheres[$identifier] = '[a-zA-Z0-9-_]+';
        }

        return $this;
    }

    public function withPolicy($handler)
    {
        $this->policyHandler = $handler;
    }

    public function register()
    {
        $path = $this->compilePath($this->path);

        $options = [
            'methods' => $this->method,
            'callback' => [$this, 'callback'],
            'permission_callback' => [$this, 'permissionCallback']
        ];

        return register_rest_route($this->prefix, "/{$path}", $options);
    }

    protected function getValue($value)
    {
        if (array_key_exists($value, $this->predefinedNamedRegx)) {
            return $this->predefinedNamedRegx[$value];
        }

        return $value;
    }

    protected function getPolicyHandler($policyHandler)
    {
        if (strpos($policyHandler, '@') !== false) return $policyHandler;

        if (strpos($policyHandler, '::') !== false) return $policyHandler;
        
        if (!function_exists($policyHandler)) {
            if (is_string($this->handler) && strpos($this->handler, '@') !== false) {
                list($_, $method) = explode('@', $this->handler);
                $policyHandler = $policyHandler . '@' . $method;
            } else if (is_array($this->handler)) {
                $policyHandler = $policyHandler . '@' . $this->handler[1];
            }
        }

        return $policyHandler;
    }

    protected function compilePath($path)
    {
        return preg_replace_callback('/{(.*?)}/', function($match) use ($path) {
            // Default regx
            $regx = '[^\s]+';
            
            if (isset($this->wheres[$match[1]])) {
                $regx = $this->wheres[$match[1]];
            }

            return "(?P<" . $match[1] . ">" . $regx . ")";

        }, $path);
    }

    public function callback(\WP_REST_Request $request)
    {
        try {
            $this->setRestRequest($request);

            $response = $this->app->call(
                $this->app->parseRestHandler($this->handler),
                array_values($request->get_url_params())
            );

            if (!($response instanceof \WP_REST_Response)) {
                if (is_wp_error($response)) {
                    $response = $this->sendWPError($response);
                } else {
                    $response = $this->app->response->sendSuccess($response);
                }
            }

            return $response;

        } catch (\FluentValidator\ValidationException $e) {
            return $this->app->response->sendError(
                $e->errors(), $e->getCode()
            );
        } catch (\Exception $e) {
            return $this->app->response->sendError([
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function permissionCallback(\WP_REST_Request $request)
    {
        $this->setRestRequest($request);

        if (!$this->policyHandler) {
            return true;
        }

        $policyHandler = $this->app->parsePolicyHandler(
            $this->getPolicyHandler($this->policyHandler)
        );

        return $this->app->call($policyHandler, $request->get_url_params());
    }

    protected function setRestRequest($request)
    {
        if (!$this->app->bound('wprestrequest')) {
            $this->app->instance('wprestrequest', $request);
        }
    }

    protected function sendWPError($response)
    {
        $code = $response->get_error_code();

        return $this->app->response->sendError(
            $response->get_error_messages(),
            is_numeric($code) ? $code : null
        );
    }
}
