<?php

namespace Mini\Exceptions;
use Exception;
use Mini\Helpers\Response;

class MiniException extends Exception
{

    /**
     * @var null
     */
    private $message = null;
    /**
     * @var int
     */
    private $statusCode = 500;

    function __construct($message, $code, Exception $previous)
    {
        $this->message = $message;
        $this->render();
    }

    public function render() {

        response()->json(['teste'], 200);

    }

}