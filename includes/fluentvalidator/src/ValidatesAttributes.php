<?php

namespace FluentValidator;

use Countable;
use InvalidArgumentException;
use FluentValidator\Contracts\File;

trait ValidatesAttributes
{
    /**
     * Require a certain number of parameters to be present.
     *
     * @param int $count
     * @param array $parameters
     * @param string $rule
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }

    /**
     * Get the size of an attribute.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getSize($attribute, $value)
    {
        // This method will determine if the attribute is a number, string, or file and
        // return the proper size accordingly. If it is a number, then number itself
        // is the size. If it is a file, we take kilobytes, and for a string the
        // entire length of the string will be considered the attribute size.
        $type = $this->deduceType($value);

        switch ($type) {
            case 'numeric':
                return $value;
            case 'array':
                return count($value);
            case 'file':
                return $value->getSize() / 1024;
            default:
                return mb_strlen($value);
        }
    }

    /**
     * Deduce the value type of an attribute.
     *
     * @param $value
     *
     * @return string
     */
    protected function deduceType($value)
    {
        if (is_numeric($value)) {
            return 'numeric';
        } elseif (is_array($value)) {
            return 'array';
        } elseif ($value instanceof File) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Convert the given values to boolean if they are string "true" / "false".
     *
     * @param array $values
     *
     * @return array
     */
    protected function convertValuesToBoolean($values)
    {
        return array_map(function ($value) {
            if ($value === 'true') {
                return true;
            } elseif ($value === 'false') {
                return false;
            }

            return $value;
        }, $values);
    }

    /**
     * Validate that a required attribute exists.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() != '';
        }

        return true;
    }

    /**
     * Validate that an attribute exists when another attribute has a given value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param mixed $parameters
     *
     * @return bool
     */
    protected function validateRequiredIf($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'required_if');

        $other = Arr::get($this->data, $parameters[0]);

        $values = array_slice($parameters, 1);

        if (is_bool($other)) {
            $values = $this->convertValuesToBoolean($values);
        }

        if (in_array($other, $values)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function validateEmail($attribute, $value)
    {
        return ! ! is_email($value);
    }

    /**
     * Validate the size of an attribute.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateSize($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'size');

        return $this->getSize($attribute, $value) == $parameters[0];
    }

    /**
     * Validate the size of an attribute is greater than a minimum value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateMin($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    /**
     * Validate the size of an attribute is less than a maximum value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateMax($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max');

        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    /**
     * Validate that two attributes match.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateSame($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'same');

        $other = @$this->data[$parameters[0]];

        return $value === $other;
    }

    /**
     * Validate that an attribute is a valid URL.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function validateUrl($attribute, $value)
    {
        return (bool) wp_http_validate_url($value);
    }

    /**
     * Validate that an attribute is numeric.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function validateNumeric($attribute, $value)
    {
        return is_numeric($value);
    }

    /**
     * Validate the guessed extension of a file upload is in a set of file extensions.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateMimes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        /**
         * @var $value \FluentValidator\Contracts\File
         */
        return $value->getPath() != '' && in_array($value->guessExtension(), $parameters);
    }

    /**
     * Validate the MIME type of a file upload attribute is in a set of MIME types.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    protected function validateMimetypes($attribute, $value, $parameters)
    {
        if (! $this->isValidFileInstance($value)) {
            return false;
        }

        if ($this->shouldBlockPhpUpload($value, $parameters)) {
            return false;
        }

        return $value->getPath() != '' &&
            (
                in_array($value->getMimeType(), $parameters) ||
                in_array(explode('/', $value->getMimeType())[0].'/*', $parameters)
            );
    }

    /**
     * Check that the given value is a valid file instance.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidFileInstance($value)
    {
        return $value instanceof File && $value->isValid();
    }

    /**
     * Check if PHP uploads are explicitly allowed.
     *
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function shouldBlockPhpUpload($value, $parameters)
    {
        if (in_array('php', $parameters)) {
            return false;
        }

        /**
         * @var $value \FluentValidator\Contracts\File
         */
        return strtolower($value->getClientOriginalExtension()) === 'php';
    }

    /**
     * Validate that an attribute exists even if not filled.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    protected function validatePresent($attribute, $value)
    {
        return Arr::has($this->data, $attribute);
    }

    /**
     * Validate that an attribute is unique in a given table
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     *
     * @return bool
     */
    protected function validateUnique($attribute, $value, $parameters)
    {
        global $wpdb;
        
        if ($parameters && !$parameters[0]) {
            unset($parameters[0]);
        }

        $this->requireParameterCount(1, $parameters, 'unique');

        if (!empty($parameters[1]) && strtolower($parameters[1]) != 'null') {
            $attribute = $parameters[1];
        }

        $bindings = [$value];

        $query = "SELECT * FROM {$wpdb->prefix}{$parameters[0]} WHERE {$attribute} = %s";
        
        if (count($parameters) > 2) {
            $ignorekey = 'id';

            if (!empty($parameters[3]) && strtolower($parameters[3]) != 'null') {
                $ignorekey = $parameters[3];
            }

            if ($parameters[2] && strtolower($parameters[2]) != 'null') {
                $query .= " and {$ignorekey} != %d";
                $bindings[] = $parameters[2];
            }
        }

        if (count($parameters) > 3) {
            if(count(array_slice($parameters, 0, 4)) == 4) {
                $extraWhereClauses = array_slice($parameters, 4);
                foreach (array_chunk($extraWhereClauses, 2) as $where) {
                    if (count($where) == 2) {
                        if (is_numeric($where[1])) {
                            $placeHolder = strpos($where[1], ".") === true ? '%f' : '%d';
                        } else {
                            $placeHolder = '%s';
                        }
                        $query .= " and {$where[0]} = {$placeHolder}";
                        $bindings[] = $where[1];
                    }
                }
            }
        }
        
        return is_null($wpdb->get_row($wpdb->prepare($query, $bindings)));
    }
}
