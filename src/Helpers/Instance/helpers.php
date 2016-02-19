<?php

use Mini\Helpers\Response;

use Mini\Container;
use Dotenv\Dotenv;

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

if (!function_exists('env')) {
    $env = null;

    function env($name, $default = null) {
        global $env;

        if (! $env) {
            $kernel = app()->get('Mini\Kernel');
            $env = new Dotenv($kernel->getBasePath());
            $env->load();
        }

        $result = getenv($name);
        return $result !== null ? $result : $default;
    }
}
