<?php

namespace FluentValidator;

class ValidationRuleParser
{
    /**
     * The data being validated.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new validation rule parser.
     *
     * @param array $data
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Parse the human-friendly rules into a full rules array for the validator.
     *
     * @param $rules
     *
     * @return array
     */
    public function explode($rules)
    {
        return $this->explodeRules($rules);
    }

    /**
     * Explode the rules into an array of explicit rules.
     *
     * @param array $rules
     *
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $attribute => $rule) {
            if (mb_strpos($attribute, '*') !== false) {
                $rules = $this->explodeWildcardRules($rules, $attribute, [$rule]);

                unset($rules[$attribute]);
            } else {
                $rules[$attribute] = $this->explodeExplicitRule($rule);
            }
        }

        return $rules;
    }

    /**
     * Explode the explicit rule into an array if necessary.
     *
     * @param mixed $rule
     *
     * @return array
     */
    protected function explodeExplicitRule($rule)
    {
        if (is_string($rule)) {
            return explode('|', $rule);
        }

        var_dump('check laravel');
    }

    /**
     * Define a set of rules that apply to each element in an array attribute.
     *
     * @param array $results
     * @param string $attribute
     * @param string|array $rules
     *
     * @return array
     */
    protected function explodeWildcardRules($results, $attribute, $rules)
    {
        $pattern = str_replace('\*', '[^\.]*', preg_quote($attribute));

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($attribute)) === $attribute || (bool) preg_match('/^'.$pattern.'\z/', $key)) {
                foreach ((array) $rules as $rule) {
                    $results = $this->mergeRules($results, $key, $rule);
                }
            }
        }

        return $results;
    }

    /**
     * Merge additional rules into a given attribute(s).
     *
     * @param array $results
     * @param string|array $attribute
     * @param string|array $rules
     *
     * @return array
     */
    public function mergeRules($results, $attribute, $rules = [])
    {
        if (is_array($attribute)) {
            foreach ((array) $attribute as $innerAttribute => $innerRules) {
                $results = $this->mergeRulesForAttribute($results, $innerAttribute, $innerRules);
            }

            return $results;
        }

        return $this->mergeRulesForAttribute(
            $results, $attribute, $rules
        );
    }

    /**
     * Merge additional rules into a given attribute.
     *
     * @param array $results
     * @param string $attribute
     * @param string|array $rules
     *
     * @return array
     */
    protected function mergeRulesForAttribute($results, $attribute, $rules)
    {
        $array = $this->explodeRules([$rules]);

        $merge = reset($array);

        $results[$attribute] = array_merge(
            isset($results[$attribute]) ? $this->explodeExplicitRule($results[$attribute]) : [], $merge
        );

        return $results;
    }

    /**
     * Extract the rule name and parameters from a rule.
     *
     * @param $rule
     *
     * @return array
     */
    public static function parse($rule)
    {
        $parameters = [];

        if (strpos($rule, ':') !== false) {
            list($rule, $parameter) = explode(':', $rule, 2);

            $parameters = str_getcsv($parameter);
        }

        return [trim($rule), $parameters];
    }
}