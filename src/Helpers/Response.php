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
        $this->setBody(json_encode($object));
        $this->setStatusCode($statuCode);
        $this->header('Content-type', 'application/json');
        $this->make();
    }

}