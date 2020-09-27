<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\App;
use FluentValidator\ValidationException;

abstract class Controller
{
    /**
     * @var \FluentCrm\App\Plugin
     */
    protected $app = null;

    /**
     * @var \FluentCrm\Includes\Request\Request
     */
    protected $request = null;

    /**
     * @var \FluentCrm\Includes\Response\Response
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
        $validator = fluentcrmFluentValidator($data, $rules, $messages);

        if ($validator->validate()->fails()) {
            throw new ValidationException(
                'Unprocessable Entity!', 422, null, $validator->errors()
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

    public function sendError($data = null, $code = 423)
    {
        return $this->response->sendError($data, $code);
    }

    public function validationErrors($data = null, $code = 422)
    {
        if ($data instanceof ValidationException) {
            $data = $data->errors();
        }

        return $this->sendError($data, $code);
    }
}
