<?php

namespace Mini\Validation;

use Exception;

class ValidationException extends Exception
{
    public $errors;

    public function __construct(array $errors)
    {
        parent::__construct('ValidationException');
        $this->errors = $errors;
    }
}
