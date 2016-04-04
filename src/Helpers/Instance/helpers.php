<?php

use Mini\Helpers\Response;

use Mini\Container;
use Mini\Entity\RawValue;
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
            try {
                $kernel = app()->get('Mini\Kernel');
                $env = new Dotenv($kernel->getBasePath());
                $env->load();
            } catch (\Exception $e) {
                $env = null;
            }
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

if ( ! function_exists('array_only'))
{
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

if ( ! function_exists('array_except'))
{
    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }
}

if ( ! function_exists('camel_case'))
{
    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    function camel_case($value)
    {
        return lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $value
                    )
                )
            )
        );
    }
}

if ( ! function_exists('quote_sql'))
{
    /**
     * Quote sql values using a array
     *
     * @param  array  $pieces
     * @return string
     */
    function map_quotes(array $pieces)
    {
        return implode(
            '.',
            array_map(
                function ($item) {
                    if ($item == '*' || is_numeric($item)) {
                        return $item;
                    }

                    $result = '`' . str_replace('`', '', $item) . '`';
                    // TODO: Refactor subquery to dont treat parentheses as field name
                    $result = str_replace('`(', '(`', $result); 
                    $result = str_replace(')`', '`)', $result);
                    return $result;
                },
                $pieces
            )
        );
    }

    /**
     * Quote sql values considering special cases.
     *
     * - Turns user.name into `user`.`name`
     * - Turns user.name as user_name into `user`.`name` as `user_name`
     * - Turns 1 as total into 1 as `total'
     * - Ignore functions as CONCAT(user.name, user.last_name)
     *
     * @param  string  $value
     * @return string
     */
    function quote_sql($value)
    {
        $isRaw = preg_match('/[_\w]+\([^)]+\)/', $value);

        if ($isRaw) {
            return $value;
        }

        $value = str_replace(' AS ', ' as ', $value);
        $separator = strstr($value, ' as ') ? ' as ' : ' ';
        $asPieces = explode($separator, $value);
        $beforeAsPieces = map_quotes(explode('.', $asPieces[0]));
        $afterAsPieces = isset($asPieces[1]) ? explode('.', $asPieces[1]) : null;
        if ($afterAsPieces) {
            $afterAsPieces = map_quotes($afterAsPieces);
        }

        return $afterAsPieces ? $beforeAsPieces . $separator . $afterAsPieces : $beforeAsPieces;
    }
}
