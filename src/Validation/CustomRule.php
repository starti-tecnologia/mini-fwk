<?php

namespace Mini\Validation;

class CustomRule
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var callable
     */
    public $message;

    /**
     * @var callback
     */
    public $callback;

    public function __construct($name, $message, $callback)
    {
        $this->name = $name;
        $this->message = $message;
        $this->callback = $callback;
    }
}
