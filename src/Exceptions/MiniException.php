<?php

namespace Mini\Exceptions;
use Exception;
use Mini\Helpers\Response;

class MiniException extends Exception
{

    /**
     * @var null
     */
    private $msg = null;
    /**
     * @var int
     */
    private $statusCode = 500;

    function __construct($message)
    {
        $this->msg = $message;
        $this->render();
    }

    public function render() {

        response()->json([
            'data' => [],
            'message' => $this->msg
        ], $this->statusCode);

    }

}