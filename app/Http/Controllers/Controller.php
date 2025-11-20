<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\App;
use FluentCrm\Framework\Validator\ValidationException;
use FluentCrm\Framework\Validator\Validator;

/**
 *  abstract REST API Controller Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
abstract class Controller
{
    /**
     * @var \FluentCrm\App\App
     */
    protected $app = null;

    /**
     * @var \FluentCrm\Framework\Request\Request
     */
    protected $request = null;

    /**
     * @var \FluentCrm\Framework\Response\Response
     */
    protected $response = null;

    public function __construct()
    {
        $this->app = App::getInstance();
        $this->request = $this->app['request'];
        $this->response = $this->app['response'];
    }

    public function validate($data, $rules, $messages = [])
    {
        $validator = new Validator($data, $rules, $messages);


        if ($validator->validate()->fails()) {
            // Sanitize validation error messages before returning them
            $errors = $validator->errors();
            if (is_array($errors)) {
                array_walk_recursive($errors, function (&$value) {
                    if (is_string($value)) {
                        $value = sanitize_text_field($value);
                    }
                });
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Sanitization is already done above
            throw new ValidationException(
                esc_html__('Unprocessable Entity!', 'fluent-crm'),
                422,
                null,
                $errors
            );
        }

        return $data;
    }

    public function send($data = null, $code = 200)
    {
        return $this->response->send($data, $code);
    }

    public function sendSuccess($data = null, $code = 200)
    {
        return $this->response->sendSuccess($data, $code);
    }

    public function sendError($data = null, $code = 422)
    {
        return $this->response->sendError($data, $code);
    }

    public function validationErrors($data = null, $code = 422)
    {
        if ($data instanceof ValidationException) {
            $data = $data->errors();
        }

        // Sanitize error payload before sending the response to prevent unescaped output
        if (is_array($data)) {
            array_walk_recursive($data, function (&$value) {
                if (is_string($value)) {
                    $value = sanitize_text_field($value);
                }
            });
        } elseif (is_string($data)) {
            $data = sanitize_text_field($data);
        }

        return $this->sendError($data, $code);
    }
}
