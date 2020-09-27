<?php

namespace FluentCrm\Includes\Response;

class Response
{
    protected $app = null;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function json($data = null, $code = 200)
    {
        wp_send_json($data, $code);
    }

    public function send($data = null, $code = 200)
    {
        return new \WP_REST_Response($data, $code);
    }

    public function sendSuccess($data = null, $code = 200)
    {
         return new \WP_REST_Response($data, $code);
    }

    public function sendError($data = null, $code = 423)
    {
         return new \WP_REST_Response($data, $code);
    }
}
