<?php

namespace FluentValidator;

class Validator
{
    use ValidatesAttributes, MessageBag;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * All of the error messages.
     *
     * @var array
     */
    protected $messages;

    /**
     * All of the user provided messages.
     *
     * @var array
     */
    protected $customMessages = [];

    /**
     * The validation rules that imply the field is required.
     *
     * @var array
     */
    protected $implicitRules = [
        'Required',
        'Filled',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
        'RequiredIf',
        'RequiredUnless',
        'Accepted',
        'Present',
    ];

    /**
     * Create a new Validator instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return void
     */
    public function __construct(array $data = [], array $rules = [], array $messages = [])
    {
        $this->data = $data;

        $this->setRules($rules);

        $this->messages = [];

        $this->customMessages = $messages;
    }

    /**
     * Create a new Validator instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return \FluentValidator\Validator
     */
    public static function make(array $data = [], array $rules = [], array $messages = [])
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Set the validation rules.
     *
     * @param array $rules
     *
     * @return $this
     */
    protected function setRules(array $rules = [])
    {
        $this->rules = array_merge_recursive(
            $this->rules, (new ValidationRuleParser($this->data))->explode($rules)
        );

        return $this;
    }

    /**
     * Validate the data against the provided rules.
     *
     * @return $this
     */
    public function validate()
    {
        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }

        return $this;
    }

    /**
     * Validate each of the attribute of the data.
     *
     * @param $attribute
     * @param $rule
     *
     * @return void
     */
    protected function validateAttribute($attribute, $rule)
    {
        list($rule, $parameters) = ValidationRuleParser::parse($rule);

        $value = $this->getValue($attribute);

        $ruleCamelCase = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rule)));

        $method = 'validate'.$ruleCamelCase;

        if ($this->shouldValidate($method, $ruleCamelCase, $attribute, $value) &&
            ! $this->$method($attribute, $value, $parameters)
        ) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Access the data by attribute name.
     *
     * @param $attribute
     *
     * @return mixed
     */
    protected function getValue($attribute)
    {
        $attribute = str_replace(['[', ']'], ['.', ''], $attribute);

        return Arr::get($this->data, $attribute);
    }

    /**
     * Add error message upon validation failed of an attribute.
     *
     * @param $attribute
     * @param $rule
     * @param $parameters
     *
     * @return void
     */
    protected function addFailure($attribute, $rule, $parameters)
    {
        $this->messages[$attribute][$rule] = $this->generate($attribute, $rule, $parameters);
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors()
    {
        return $this->messages;
    }

    /**
     * Determine if the data passes the validation rules.
     *
     * @return bool
     */
    public function passes()
    {
        return ! $this->fails();
    }

    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails()
    {
        return (bool) count($this->messages);
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param string|array $attribute
     * @param string|array $rules
     * @param callable $callback
     *
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        $payload = $this->data;

        if (call_user_func($callback, $payload)) {
            foreach ((array) $attribute as $key) {
                $this->setRules([$key => $rules]);
            }
        }

        return $this;
    }

    /**
     * Determine if the attribute has a required rule.
     *
     * @param $attribute
     *
     * @return bool
     */
    public function hasRequired($attribute)
    {
        foreach ($this->rules[$attribute] as $rule) {
            if (strpos($rule, 'required') !== false) {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * Determine if the attribute should be validated.
     *
     * @param $method
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    public function shouldValidate($method, $rule, $attribute, $value)
    {
        return $this->hasMethod($method) && $this->presentOrRuleIsImplicit($rule, $attribute, $value);
    }

    /**
     * Determines if this object has this method.
     *
     * @param $method
     *
     * @return bool
     */
    public function hasMethod($method)
    {
        return method_exists($this, $method);
    }

    /**
     * Determine if a given rule implies the attribute is required.
     *
     * @param string $rule
     *
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * @param string $rule
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        return $this->validatePresent($attribute, $value) || $this->isImplicit($rule);
    }
}