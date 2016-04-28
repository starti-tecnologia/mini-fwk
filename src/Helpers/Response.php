<?php

namespace Mini\Helpers;

class Response extends ResponseBase
{

    /**
     * @param $object
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
}
