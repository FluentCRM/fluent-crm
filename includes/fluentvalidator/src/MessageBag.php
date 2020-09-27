<?php

namespace FluentValidator;

trait MessageBag
{
    /**
     * The default message bag.
     *
     * @var array
     */
    protected $bag = [
        'email'       => 'The :attribute must be a valid email address.',
        'max'         => [
            'numeric' => 'The :attribute may not be greater than :max.',
            'file'    => 'The :attribute may not be greater than :max kilobytes.',
            'string'  => 'The :attribute may not be greater than :max characters.',
            'array'   => 'The :attribute may not have more than :max items.',
        ],
        'mimes'       => 'The :attribute must be a file of type: :values.',
        'mimetypes'   => 'The :attribute must be a file of type: :values.',
        'min'         => [
            'numeric' => 'The :attribute must be at least :min.',
            'file'    => 'The :attribute must be at least :min kilobytes.',
            'string'  => 'The :attribute must be at least :min characters.',
            'array'   => 'The :attribute must have at least :min items.',
        ],
        'numeric'     => 'The :attribute must be a number.',
        'required'    => 'The :attribute field is required.',
        'required_if' => 'The :attribute field is required when :other is :value.',
        'same'        => 'The :attribute and :other must match.',
        'size'        => [
            'numeric' => 'The :attribute must be :size.',
            'file'    => 'The :attribute must be :size kilobytes.',
            'string'  => 'The :attribute must be :size characters.',
            'array'   => 'The :attribute must contain :size items.',
        ],
        'url'         => 'The :attribute format is invalid.',
        'unique'         => 'The :attribute attribute is already taken and must be unique.',
    ];

    /**
     * Generate a validation error message.
     *
     * @param $attribute
     * @param $rule
     * @param $parameters
     *
     * @return mixed
     */
    protected function generate($attribute, $rule, $parameters)
    {
        $method = 'replace'.str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rule)));

        if ($this->hasMethod($method)) {
            return $this->$method($attribute, $parameters);
        }

        return '';
    }


    /**
     * Get the replacement text of the error message.
     *
     * @param $customMessagesKey
     * @param $bagAccessor
     *
     * @return string
     */
    protected function getReplacementText($customMessagesKey, $bagAccessor)
    {
        return isset($this->customMessages[$customMessagesKey])
            ? $this->customMessages[$customMessagesKey]
            : Arr::get($this->bag, $bagAccessor, '');
    }

    /**
     * Make bag accessor key.
     *
     * @param $attribute
     * @param $rule
     *
     * @return string
     */
    protected function makeBagKey($attribute, $rule)
    {
        $type = $this->deduceType($this->getValue($attribute));

        return $rule.'.'.$type;
    }

    /**
     * Replace all place-holders for the required rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceRequired($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.required', 'required');

        return str_replace(':attribute', $attribute, $text);
    }

    /**
     * Replace all place-holders for the required if rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceRequiredIf($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.required_if', 'required_if');

        return str_replace([':attribute', ':other', ':value'], [$attribute, $parameters[0], $parameters[1]], $text);
    }

    /**
     * Replace all place-holders for the email rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceEmail($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.email', 'email');

        return str_replace(':attribute', $attribute, $text);
    }

    /**
     * Replace all place-holders for the size rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceSize($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.size', $this->makeBagKey($attribute, 'size'));

        return str_replace([':attribute', ':size'], [$attribute, $parameters[0]], $text);
    }

    /**
     * Replace all place-holders for the min rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceMin($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.min', $this->makeBagKey($attribute, 'min'));

        return str_replace([':attribute', ':min'], [$attribute, $parameters[0]], $text);
    }

    /**
     * Replace all place-holders for the max rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceMax($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.max', $this->makeBagKey($attribute, 'max'));

        return str_replace([':attribute', ':max'], [$attribute, $parameters[0]], $text);
    }

    /**
     * Replace all place-holders for the min rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceSame($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.same', 'same');

        return str_replace([':attribute', ':other'], [$attribute, $parameters[0]], $text);
    }

    /**
     * Replace all place-holders for the url rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceUrl($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.url', 'url');

        return str_replace(':attribute', $attribute, $text);
    }

    /**
     * Replace all place-holders for the numeric rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceNumeric($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.numeric', 'numeric');

        return str_replace(':attribute', $attribute, $text);
    }

    /**
     * Replace all place-holders for the mimes rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceMimes($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.mimes', 'mimes');

        return str_replace([':attribute', ':values'], [$attribute, implode(', ', $parameters)], $text);
    }

    /**
     * Replace all place-holders for the mimetypes rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceMimetypes($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.mimetypes', 'mimetypes');

        return str_replace([':attribute', ':values'], [$attribute, implode(', ', $parameters)], $text);
    }

    /**
     * Replace all place-holders for the unique rule.
     *
     * @param $attribute
     * @param $parameters
     *
     * @return string
     */
    protected function replaceUnique($attribute, $parameters)
    {
        $text = $this->getReplacementText($attribute.'.unique', 'unique');

        return str_replace(':attribute', $attribute, $text);
    }
}
