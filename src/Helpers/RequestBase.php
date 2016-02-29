<?php

namespace Mini\Helpers;


class RequestBase
{

    protected static function parse() {
        $json = file_get_contents('php://input');
        $obj = json_decode($json, true);

        return $obj;
    }

}