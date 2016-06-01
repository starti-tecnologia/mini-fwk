<?php

use Mini\Validation\Validator;
use Mini\Exceptions\ValidationException;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/ValidationEntityStub.php';
    }

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
                'date' => '',
                'city' => 'a',
                'state' => 'dsadsadsa',
                'age' => 1,
                'quantity' => 999,
                'is_male' => false
            ]
        );

        try {
            $this->validator->validate([
                'first_name' => 'string|required',
                'last_name' => 'string|required',
                'email' => 'email|required|unique',
                'password' => 'string:100|required',
                'date' => 'datetime',
                'city' => 'string:255:3',
                'state' => 'string:2',
                'age' => 'integer|min:18',
                'quantity' => 'integer|max:18',
                'is_male' => 'boolean|required',
                'is_test' => 'boolean|required'
            ]);
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'first_name' => ['The first_name field is required.'],
            'email' => ['The email field is email.'],
            'date' => ['The date field is datetime.'],
            'city' => ['The city field min length is 3.'],
            'state' => ['The state field max length is 2.'],
            'age' => ['The age field minimum value is 18.'],
            'quantity' => ['The quantity field maximum value is 18.'],
            'is_test' => ['The is_test field is required.'],
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

    public function testIsValidatingInnerFields()
    {
        $exception = null;

        $this->validator = new Validator;
        $this->validator->setData(
            [
                'contents' => 'Jonh',
                'author' => [
                    'guid' => 'dsada',
                    'name' => null
                ]
            ]
        );

        try {
            $this->validator->validate(
                [
                    'contents' => 'string|required',
                    'author.guid' => 'string|required',
                    'author.name' => 'string|required',
                ]
            );
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'author.name' => ['The author.name field is required.']
        ], $exception->errors);
    }

    public function testIsValidatingEntities()
    {
        $exception = null;

        $this->validator = new Validator;
        $this->validator->setData([
            'name' => 'Hi',
            'child1' => [
                'name' => 'Name 2'
            ],
            'child2' => []
        ]);

        try {
            $this->validator->validateEntities(
                [
                    '*' => new ValidationEntityStub,
                    'child1'  => new ValidationEntityStub,
                    'child2'  => new ValidationEntityStub
                ]
            );
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $this->assertEquals([
            'child2.name' => ['The child2.name field is required.']
        ], $exception->errors);
    }
}
