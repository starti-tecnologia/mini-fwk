<?php

namespace Mini\Helpers;

class Response extends ResponseBase
{

    /**
     * @param array $object
     * @param int $statuCode
     */
    public function json($object, $statuCode = 200)
    {
        $text = json_encode($object);

        if (env('GZIP')) {
            $text = gzencode($text, -1);
            $this->header('Content-Encoding', 'gzip');
        }

        $this->setStatusCode($statuCode);
        $this->header('Content-Type', 'application/json');
        $this->setBody($text);
        $this->make();
    }

    /**
     * @param string $text
     * @param int $statuCode
     */
    public function text($text, $statuCode = 200)
    {
        if (env('GZIP')) {
            $text = gzencode($text, -1);
            $this->header('Content-Encoding', 'gzip');
        }

        $this->setStatusCode($statuCode);
        $this->setBody($text);
        $this->make();
    }
}
