<?php

namespace FluentCrm\Includes\Core;

use FluentValidator\Validator;
use FluentCrm\Includes\Core\App;
use FluentValidator\ValidationException;

abstract class RequestGuard
{
    public function rules()
    {
        return [];
    }

    public function messages()
    {
        return [];
    }

    public function validate(Validator $validator = null)
    {
        try {
            $validator = $validator ?: fluentcrmFluentValidator();

            $rules = (array) $this->rules();

            if (!$rules) return;

            $validator = $validator->make($this->all(), $rules, (array) $this->messages());

            if ($validator->validate()->fails()) {
                throw new ValidationException('Unprocessable Entity!', 422, null, $validator->errors());
            }
        } catch (ValidationException $e) {
            
            if (defined('REST_REQUEST') && REST_REQUEST) {
                throw $e;
            } else {
                wp_send_json($e->errors(), 422);
            }
        }
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

    public function __call($method, $params)
    {
        return call_user_func_array(
            [App::getInstance('request'), $method], $params
        );
    }
}
