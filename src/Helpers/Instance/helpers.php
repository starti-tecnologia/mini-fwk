<?php

use Mini\Helpers\Response;

if (!function_exists('response')) {

    function response() {
        return new Response();
    }

}

if (!function_exists('app')) {

    function app() {

    }

}