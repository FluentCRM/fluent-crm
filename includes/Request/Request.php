<?php

namespace FluentCrm\Includes\Request;

use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Core\Application;

class Request
{
    use FileHandler, // handles HTTP files
        Cleaner; // cleans Request data

    protected $app = null;
    protected $headers = array();
    protected $server = array();
    protected $cookie = array();
    protected $json = array();
    protected $get = array();
    protected $post = array();
    protected $files = array();
    protected $request = array();
    protected $wpRestRequest = false;

    public function __construct(Application $app, $get, $post, $files)
    {
        $this->app = $app;
        $this->server = $_SERVER;
        $this->cookie = $_COOKIE;
        $this->files = $this->prepareFiles($files);

        $this->request = array_merge(
            $this->get = $this->clean($get),
            $this->post = $this->clean($post)
        );
    }

    /**
     * Variable exists
     * @param  string $key
     * @return bool
     */
    public function exists($key)
    {
        return Arr::has($this->inputs(), $key);
    }

    /**
     * Variable exists and has truthy value
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->exists($key) && !empty(Arr::get($this->inputs(), $key));
    }

    public function set($key, $value)
    {
        Arr::set($this->inputs(), $key, $value);

        return $this;
    }

    public function all()
    {
        return $this->get();
    }

    public function get($key = null, $default = null)
    {
        $inputs = $this->inputs();

        return Arr::get($inputs, $key, $default);
    }

    public function getJson($key = null, $default = null)
    {
        $data = $this->get($key, $default);

        $array = json_decode($data, true);

        if (is_array($array) && json_last_error() == JSON_ERROR_NONE) {
            return $array;
        }

        return $data;
    }

    /**
     * Get the files from the request.
     *
     * @return array
     */
    public function files()
    {
        return $this->files;
    }

    public function query($key = null, $default = null)
    {
        return $key ? Arr::get($this->get, $key, $default) : $this->get;
    }

    public function post($key = null, $default = null)
    {
        return $key ? Arr::get($this->post, $key, $default) : $this->post;
    }

    public function only($keys)
    {
        return Arr::only($this->inputs(), $keys);
    }

    public function except($args)
    {
        return Arr::except($this->inputs(), $args);
    }

    public function merge(array $data = [])
    {
        $this->request = array_replace($this->inputs(), $data);

        return $this;
    }

    /**
     * Get all inputs
     * @return array $this->request
     */
    protected function inputs()
    {
        if (!$this->wpRestRequest && $this->app->bound('wprestrequest')) {
            $this->wpRestRequest = true;
            $this->request = array_merge(
                $this->request, $this->app->wprestrequest->get_params()
            );
        }

        return $this->request;
    }

    /**
     * Get user ip address
     * @return string
     */
    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $this->server('HTTP_CLIENT_IP');
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->server('REMOTE_ADDR');
        }

        return $ip;
    }

    public function server($key = null, $default = null)
    {
        return $key ? Arr::get($this->server, $key, $default) : $this->server;
    }

    public function header($key = null, $default = null)
    {
        if (!$this->headers) {
            $this->headers = $this->setHeaders();
        }

        return $key ? Arr::get($this->headers, $key, $default) : $this->headers;
    }

    public function cookie($key = null, $default = null)
    {
        return $key ? Arr::get($this->cookie, $key, $default) : $this->cookie;
    }

    /**
     * Taken and modified from Symfony
     */
    public function setHeaders()
    {
        $headers = array();
        $parameters = $this->server;
        $contentHeaders = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);
        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } // CONTENT_* are not prefixed with HTTP_
            elseif (isset($contentHeaders[$key])) {
                $headers[$key] = $value;
            }
        }

        if (isset($parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($parameters['PHP_AUTH_PW']) ? $parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (count($exploded) == 2) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                } elseif (empty($parameters['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $parameters['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      http://php.net/manual/en/reserved.variables.server.php
                     */
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }

        if (isset($headers['AUTHORIZATION'])) {
            return $headers;
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }

        return $headers;
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get an input element from the request.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamyc method calls (specially for WP_rest_request)
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        if ($this->app->bound('wprestrequest')) {

            if (!method_exists($this->app->wprestrequest, $method)) {
                $method = strtolower(
                    preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $method)
                );
            }

            return call_user_func_array([$this->app->wprestrequest, $method], $params);
        }
    }
}
