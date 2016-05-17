<?php

namespace Mini\Validation;

use Mini\Entity\Entity;
use Mini\Helpers\Request;
use Mini\Entity\Definition\DefinitionParser;
use Mini\Exceptions\ValidationException;

class Validator
{
    /**
     * @var DefinitionParser
     */
    private $definitionParser;

    /**
     * @var $rules
     */
    private $rules = [
        'required' => 'The %s field is required.',
        'char' => 'The %s field is string.',
        'string' => 'The %s field is string.',
        'text' => 'The %s field is string.',
        'integer' => 'The %s field is integer.',
        'float' => 'The %s field is number.',
        'double' => 'The %s field is number.',
        'decimal' => 'The %s field is number.',
        'boolean' => 'The %s field is boolean.',
        'date' => 'The %s field is date.',
        'datetime' => 'The %s field is datetime.',
        'time' => 'The %s field is time.',
        'email' => 'The %s field is email.',
        'maxLength' => 'The %s field max length is %s.',
        'minLength' => 'The %s field min length is %s.',
        'min' => 'The %s field minimum value is %s.',
        'max' => 'The %s field maximum value is %s.',
    ];

    /**
     * @var $customRules
     */
    private $customRules = [];

    /**
     * @var array
     */
    private $errors;

    /**
     * @var array
     */
    private $data;

    public function __construct()
    {
        $this->definitionParser = new DefinitionParser;
        $this->customRules = [];

        $this->reset();
    }

    public function reset()
    {
        $this->errors = [];
    }

    /**
     * Parse entity definition rules against current data
     *
     * @param \Mini\Entity\Entity Entity
     * @throws \Mini\Exceptions\ValidationException
     */
    public function validateEntity(Entity $entity, $extraDefinition = [])
    {
        $definition = $entity->fillable ?
            array_only($entity->definition, $entity->fillable) :
            $entity->definition;

        if ($extraDefinition) {
            $definition = array_merge($definition, $extraDefinition);
        }

        $this->validate($definition);
    }

    /**
     * Merge and validate entities rules by keys, example:
     *
     * $validator->validateEntities([
     *    '*' => new Retailer,
     *    'owner' => new User
     * ]);
     *
     * @param array $entities
     * @throws \Mini\Exceptions\ValidationException
     */
    public function validateEntities(array $entities, $extraDefinition = [])
    {
        $parentDefinition = [];

        foreach ($entities as $key => $entity) {
            $childDefinition = $entity->fillable ?
                array_only($entity->definition, $entity->fillable) :
                $entity->definition;

            if ($key === '*') {
                $parentDefinition = array_merge($parentDefinition, $childDefinition);
            } else {
                foreach ($childDefinition as $innerKey => $value) {
                    $parentDefinition[$key . '.' . $innerKey] = $value;
                }
            }
        }

        if ($extraDefinition) {
            $parentDefinition = array_merge($parentDefinition, $extraDefinition);
        }

        $this->validate($parentDefinition);
    }

    /**
     * Parse validation rules against current data
     *
     * @param $rules Rules definition
     * @throws \Mini\Exceptions\ValidationException
     */
    public function validate($rules)
    {
        $definition = $this->definitionParser->parse($rules);
        $this->data = $this->getData();

        foreach ($definition as $attribute => $rules) {
            $isRequired = isset($rules['required']);

            // If is required fail, no other rule must be checked
            if ($isRequired) {
                if (! $this->validateAttribute($attribute, 'required', $rules['required'])) {
                    $this->addError($attribute, 'required', $rules['required']);
                    continue;
                }

                unset($rules['required']);
            } elseif ($this->isAttributeEmpty($attribute)) {
                continue;
            }

            $filteredRules = [];

            foreach ($rules as $rule => $parameters) {
                $filteredRules[$rule] = $parameters;

                if ($rule === 'string') {
                    $filteredRules['maxLength'] = [isset($parameters[0]) ? $parameters[0] : 255];
                    $filteredRules['minLength'] = [isset($parameters[1]) ? $parameters[1] : 0];
                } elseif ($rule == 'text') {
                    $filteredRules['maxLength'] = [isset($parameters[0]) ? $parameters[0] : 65535];
                    $filteredRules['minLength'] = [isset($parameters[1]) ? $parameters[1] : 0];
                }
            }

            foreach ($filteredRules as $rule => $parameters) {
                if (! isset($this->rules[$rule]) && ! isset($this->customRules[$rule])) {
                    continue;
                }

                $isValid = $this->validateAttribute($attribute, $rule, $parameters);

                if (! $isValid) {
                    $this->addError($attribute, $rule, $parameters);
                }
            }
        }

        if (count($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }

    public function addError($attribute, $rule, array $parameters)
    {
        if (! isset($this->errors[$attribute])) {
            $this->errors[$attribute] = [];
        }

        $message = isset($this->rules[$rule]) ? $this->rules[$rule] : $this->customRules[$rule]->message;

        $this->errors[$attribute][] = vsprintf($message, array_merge([$attribute], $parameters));
    }

    public function isAttributeEmpty($attribute)
    {
        return array_get($this->data, $attribute) === null;
    }

    private function validateAttribute($attribute, $rule, $parameters)
    {
        $value = array_get($this->data, $attribute);

        if (isset($this->rules[$rule])) {
            $method = 'validate' . ucfirst($rule) . 'Rule';
            return $this->{$method}($value, $parameters);
        } else {
            $callback = $this->customRules[$rule]->callback;
            return $callback($value, $parameters);
        }
    }

    private function validateRequiredRule($value, array $parameters)
    {
        return !! trim($value);
    }

    private function validateStringRule($value, array $parameters)
    {
        return is_string($value);
    }

    private function validateCharRule($value, array $parameters)
    {
        $this->validateStringRule($value, $parameters);
    }

    private function validateTextRule($value, array $parameters)
    {
        $this->validateStringRule($value, $parameters);
    }

    private function validateIntegerRule($value, array $parameters)
    {
        $length = isset($parameters[0]) ? $parameters[0] : null;

        return is_int($value) && ($length === null || strlen($value) <= $length);
    }

    private function validateFloatRule($value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateDoubleRule($value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateDecimalRule($value, array $parameters)
    {
        return is_numeric($value);
    }

    private function validateBooleanRule($value, array $parameters)
    {
        return is_bool($value);
    }

    private function validateDateRule($value, array $parameters)
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/', $value);
    }

    private function validateDatetimeRule($value, array $parameters)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $value);
    }

    private function validateTimeRule($value, array $parameters)
    {
        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value);
    }

    private function validateTimestampRule($value, array $parameters)
    {
        return $this->validateDatetimeRule($value, $parameters);
    }

    private function validateEmailRule($value, array $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function setCustomRule($name, $message, $callback)
    {
        $this->customRules[$name] = new CustomRule($name, $message, $callback);
    }

    private function validateMaxLengthRule($value, array $parameters)
    {
        return strlen($value) <= $parameters[0];
    }

    private function validateMinLengthRule($value, array $parameters)
    {
        return strlen($value) >= $parameters[0];
    }


    private function validateMaxRule($value, array $parameters)
    {
        return $value <= $parameters[0];
    }

    private function validateMinRule($value, array $parameters)
    {
        return $value >= $parameters[0];
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->reset();

        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return a list of valid tags names
     *
     * @return array
     */
    public function getValidTags()
    {
        return array_merge(
            array_keys($this->rules),
            array_keys($this->customRules)
        );
    }
}
