<?php

namespace FluentCrm\Framework\Http;

use WP_REST_Response;
use ReflectionException;
use FluentCrm\Framework\Foundation\App;
use FluentCrm\Framework\Validator\ValidationException;

abstract class Controller
{
    protected $app = null;
    protected $request = null;
    protected $response = null;

    public function __construct()
    {
        $this->app = App::getInstance();
        $this->request = $this->app['request'];
        $this->response = $this->app['response'];
    }

    public function validate($data, $rules, $messages = [])
    {
        try {
            $validator = $this->app->validator->make($data, $rules, $messages);

            if ($validator->validate()->fails()) {
                throw new ValidationException(
                    'Unprocessable Entity!', 422, null, $validator->errors()
                );
            }

            return $data;

        } catch (ValidationException $e) {

            if (defined('REST_REQUEST') && REST_REQUEST) {
                throw $e;
            };

            $this->app->doCustomAction('handle_exception', $e);
        }
    }

    public function json($data = null, $code = 200)
    {
        return $this->response->json($data, $code);
    }

    public function send($data = null, $code = 200)
    {
        return $this->response->send($data, $code);
    }

    public function sendSuccess($data = null, $code = null)
    {
        return $this->response->sendSuccess($data, $code);
    }

    public function sendError($data = null, $code = 423)
    {
        return $this->response->sendError($data, $code);
    }

    public function __get($key)
    {
        try {
            return App::getInstance($key);
        } catch(ReflectionException $e) {
            $class = get_class($this);
            wp_die("Undefined property {$key} in $class");
        }
    }

    public function response($data, $code = 200)
    {
        return new WP_REST_Response($data, $code);
    }
}
