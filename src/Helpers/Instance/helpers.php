<?php

use Mini\Helpers\Response;

use Mini\Container;

if (!function_exists('response')) {

    function response() {
        return new Response();
    }

}

if (!function_exists('app')) {

    function app() {
       return Container::instance();
    }

}