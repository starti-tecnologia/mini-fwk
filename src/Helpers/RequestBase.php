<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 13/02/16
 * Time: 19:54
 */

namespace Mini\Helpers;


class RequestBase
{

    public static function parse() {
        print_r($_REQUEST);
    }

}