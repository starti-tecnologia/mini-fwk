<?php

use Mini\Validation\Validator;
use Mini\Exceptions\ValidationException;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public function testIsValidatingStaticRules()
    {
        $exception = null;

        $this->validator = new Validator;
        $this->validator->setData(
            [
                'first_name' => '',
                'last_name' => 'Daniel',
                'email' => 'jeferson.daniel',
                'password' => '23456',
                'user_type' => 'c451f95-91eb-4624-9a31-495c656e3ab6',
                'date' => ''
            ]
        );

        try {
            $this->validator->validate([
                'first_name' => 'string|required',
                'last_name' => 'string|required',
                'email' => 'email|required|unique',
                'password' => 'string:100|required',
                'date' => 'datetime',
            ]);
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'first_name' => ['The first_name field is required.'],
            'email' => ['The email field is email.'],
            'date' => ['The date field is datetime.']
        ], $exception->errors);
    }

    public function testIsValidatingCustomRules()
    {
        $exception = null;

        $this->validator = new Validator;

        $this->validator->setData(
            [
                'name' => 'Jeferson Daniel',
                'ip' => '192.'
            ]
        );

        $this->validator->setCustomRule(
            'ip',
            'The %s field is ip.',
            function ($value, array $parameters) {
                return filter_var($value, FILTER_VALIDATE_IP);
            }
        );

        try {
            $this->validator->validate([
                'name' => 'string|required',
                'ip' => 'string|ip|required',
            ]);
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'ip' => ['The ip field is ip.'],
        ], $exception->errors);
    }

    public function testIsNotValidatingEmptyAndNotRequiredFields()
    {
        $exception = null;

        $this->validator = new Validator;
        $this->validator->setData(
            [
                'email' => 'jeferson.daniel',
                'first_name' => 'Jeferson',
                'last_name' => null
            ]
        );

        try {
            $this->validator->validate(
                [
                    'email' => 'email',
                    'first_name' => 'string|required',
                    'last_name' => 'string',
                    'date' => 'datetime'
                ]
            );
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'email' => ['The email field is email.']
        ], $exception->errors);
    }
}
