<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 13/02/16
 * Time: 19:54
 */

namespace Mini\Helpers;


class Request extends RequestBase
{

    public static function instance() {
        static::parse();

        
    }

}