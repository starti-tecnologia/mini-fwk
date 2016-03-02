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

if ( ! function_exists('array_get'))
{
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;
        if (isset($array[$key])) return $array[$key];
        if (strstr($key, ".")) {
            foreach (explode('.', $key) as $segment) {
                if (!is_array($array) || !array_key_exists($segment, $array)) {
                    return $default;
                }
                $array = $array[$segment];
            }
        } else {
            $array = null;
        }
        return $array;
    }
}
