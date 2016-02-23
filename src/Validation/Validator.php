<?php

namespace Mini\Validation;

use Mini\Entity;

class Validator
{
    /**
     * @var array
     */
    private $rules = [
        'required', 'char', 'string', 'text', 'integer', 'float', 'double', 'decimal', 'boolean',
        'date', 'datetime', 'time', 'timestamp'
    ];

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $data;

    public function __constructor()
    {
        $this->definitionParser = new DefinitionParser;
        $this->reset();
    }

    public function reset()
    {
        $this->data = [];
        $this->errors = [];
    }

    public function validateEntity(Entity $entity)
    {
        $this->validate($entity->definition);
    }

    public function validate($rules)
    {
        $this->reset();

        $definition = $this->definitionParser->parse($rules);
        $this->data = Request::instance()->get('data');

        foreach ($definition as $attribute => $rules) {
            $value = array_get($this->data, $attribute);

            foreach ($rules as $rule => $parameters) {
                $isValid = $this->validateAttribute($attribute, $value, $parameters);

                if (! $isValid) {
                    $this->errors[] = [
                        'source' => $attribute,
                        'rule' => $rule
                    ];
                }
            }
        }
    }

    private function validateAttribute($attribute, $rule, $parameters)
    {
        $value = array_get($this->data, $attribute);
        $method = 'check' . ucfist($attribute) . 'Rule';
        $this->{$method}($value, $parameters);
    }

    private function validateRequiredRule($attribute, $value, array $parameters)
    {
        return trim($value) === '';
    }

    private function validateStringRule($attribute, $value, array $parameters)
    {
        $length = isset($parameters[0]) ? $parameters[0] : null;

        return is_string($value) && ($length === null || strlen($value) <= $length);
    }

    private function validateCharRule($attribute, $value, array $parameters)
    {
        $this->validateStringRule($attribute, $value, $parameters);
    }

    private function validateTextRule($attribute, $value, array $parameters)
    {
        $this->validateStringRule($attribute, $value, $parameters);
    }

    private function validateIntegerRule($attribute, $value, array $parameters)
    {
        $length = isset($parameters[0]) ? $parameters[0] : null;

        return is_int($value) && ($length === null || strlen($value) <= $length);
    }

    private function validateFloatRule($attribute, $value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateDoubleRule($attribute, $value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateDecimalRule($attribute, $value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateBooleanRule($attribute, $value, array $parameters)
    {
        return is_bool($value);
    }

    private function validateDateRule($attribute, $value, array $parameters)
    {

    }

    private function validateDatetimeRule($attribute, $value, array $parameters)
    {

    }

    private function validateTimeRule($attribute, $value, array $parameters)
    {

    }

    private function validateTimestampRule($attribute, $value, array $parameters)
    {

    }
}
