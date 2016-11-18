<?php

/**
 * User: coderd
 * Date: 2016/11/18
 * Time: 10:36
 */

namespace Rapkg\Validation;


class Validator
{
    private $data;

    private $rules;

    private $message;

    public function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $this->explodeRules($rules);
    }

    public static function make($data, $rules)
    {
        return new Validator($data, $rules);
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function passes()
    {
        $this->message = null;
        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (!$this->validate($attribute, $rule)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getMessage()
    {
        return $this->message;
    }

    private function validate($attribute, $rule)
    {
        list($rule, $parameters) = $this->parseRule($rule);

        $value = $this->getValue($attribute);
        $method = 'validate' . self::studly($rule);

        if ($rule != 'required' && $value === null) {
            return true;
        }
        if (!$this->$method($attribute, $value, $parameters)) {
            // TODO: set message
            return false;
        }

        return true;
    }

    private function parseRule($rule)
    {
        $parameters = [];

        if (strpos($rule, ':') !== false) {
            list($rule, $parameter) = explode(':', $rule, 2);
            $parameters = $this->parseParameters($rule, $parameter);
        }
        if (!isset($this->validateMessages[$rule])) {
            throw new \InvalidArgumentException('Invalid validator rule: ' . $rule);
        }

        return [$rule, $parameters];
    }

    /**
     * Explode the rules into an array of rules.
     *
     * @param  string|array  $rules
     * @return array
     */
    private function explodeRules($rules)
    {
        foreach ($rules as $key => &$rule) {
            $rule = (is_string($rule)) ? explode('|', $rule) : $rule;
        }

        return $rules;
    }

    private function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    private function getValue($attribute)
    {
        return $this->data[$attribute];
    }

    private function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) && count($value) < 1)) {
            return false;
        }

        return true;
    }

    private function validateInteger($attribute, $value)
    {
        return is_integer($value);
    }

    private function validateBoolean($attribute, $value)
    {
        return is_bool($value);
    }

    private function validateString($attribute, $value)
    {
        return is_string($value);
    }

    private function validateNumeric($attribute, $value)
    {
        return is_numeric($value);
    }

    private function validateAlphaNum($attribute, $value)
    {
        return preg_match('/^[0-9a-zA-Z]+$/', $value);
    }

    private function validateFloat($attribute, $value)
    {
        return is_float($value);
    }

    private function validateArray($attribute, $value)
    {
        return is_array($value);
    }

    private function validateMax($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max');

        return $this->getSize($attribute, $value) <= $parameters[0];
    }

    private function validateMin($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min');

        return $this->getSize($attribute, $value) >= $parameters[0];
    }

    private function validateBetween($attribute, $value, $parameters)
    {
        $this->requireParameterCount(2, $parameters, 'between');

        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }

    private function validateIn($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'in');

        return in_array($value, $parameters);
    }

    private function validateContain($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'contain');

        return strpos($value, $parameters[0]) !== false;
    }

    private function validateNoSpace($attribute, $value)
    {
        return strpos($value, ' ') === false;
    }

    private function validateSize($attribute, $value, $parameters)
    {
        $size = $this->getSize($attribute, $value);
        return $size == $parameters[0];
    }

    private function validateIp($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private function validateEmail($attribute, $value)
    {
        if (preg_match(
            '/^[a-zA-Z0-9]+([_\-.][a-zA-Z0-9]+)*@[a-zA-Z0-9]+([-.][a-zA-Z0-9]+)*\.[a-zA-Z0-9]+([-.][a-zA-Z0-9]+)*$/',
            $value
        )) {
            return true;
        }

        return false;
    }

    private function validateMobile($attribute, $value)
    {
        if (preg_match('/^1[34578]\d{9}$/', $value)) {
            return true;
        }
        return false;
    }

    private function validateIdNumber($attribute, $value)
    {
        $pattern = "/^([1-6][0-9]{5})([1][9]|[2][0])[0-9]{2}([0][1-9]|[1][0-2])([0][1-9]|([1]|[2])[0-9]|[3][0-1])[0-9]{3}[0-9xX]$/";
        if (preg_match($pattern, $value)) {
            return true;
        }
        return false;
    }

    /**
     * Validate that an attribute matches a date format.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    private function validateDateFormat($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'date_format');

        $parsed = date_parse_from_format($parameters[0], $value);

        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    /**
     * Validate that an attribute passes a regular expression check.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    private function validateRegex($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value);
    }

    private function requireParameterCount($count, $parameters, $rule)
    {
        if (count($parameters) < $count) {
            throw new \InvalidArgumentException("Validation rule $rule requires at least $count parameters");
        }
    }

    private function getSize($attribute, $value)
    {
        if (is_integer($value)) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        }

        return mb_strlen($value, 'UTF-8');
    }

    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    private static function studly($value)
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }
}