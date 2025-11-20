<?php

namespace FluentCrm\Framework\Response;

class Response
{
    protected $app = null;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function json($data = null, $code = 200)
    {
        return wp_send_json($data, $code);
    }

    public function send($data = null, $code = 200)
    {
        // litespeed caching bypass.
        do_action( 'litespeed_control_set_nocache', 'fluentcrm api request' );
        nocache_headers();

        return new \WP_REST_Response($data, $code);
    }

    public function sendSuccess($data = null, $code = 200)
    {
        // litespeed caching bypass.
        do_action( 'litespeed_control_set_nocache', 'fluentcrm api request' );
        nocache_headers();

        if (is_array($data)) {
            $data['__bench'] = microtime(true) - FLUENT_CRM_STARTING_TIME;
        }

        return new \WP_REST_Response($data, $code);
    }

    public function sendError($data = null, $code = 422)
    {
        // litespeed caching bypass.
        do_action( 'litespeed_control_set_nocache', 'fluentcrm api request' );
        nocache_headers();

        return new \WP_REST_Response($data, $code);
    }
}
