<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 12/02/16
 * Time: 21:36
 */

namespace Mini\Helpers;


class ResponseBase
{

    /**
     * @var array
     */
    public $headers = [];
    /**
     * @var int
     */
    private $statusCode = 200;
    /**
     * @var
     */
    private $body;

    /**
     * @param $type
     * @param $value
     */
    public function header($type, $value) {
        $this->headers[$type] = $value;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     *
     */
    private function makeHeaders() {
        foreach ($this->headers as $type => $value) {
            header($type . ":" . $value);
        }
    }

    protected function make() {
        http_response_code($this->statusCode);
        $this->makeHeaders();

        echo $this->body;
        exit;
    }

}