<?php

use Mini\Helpers\Response;

use Mini\Container;
use Mini\Entity\RawValue;
use Dotenv\Dotenv;

if (!function_exists('response')) {
    function response()
    {
        return new Response();
    }
}

if (!function_exists('app')) {
    function app()
    {
        return Container::instance();
    }

}

if (!function_exists('env')) {
    $env = null;

    function env($name, $default = null)
    {
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

if (! function_exists('array_get')) {
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

if (! function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, array_dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }
        return $results;
    }
}

if (! function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }
}

if (! function_exists('str_starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_starts_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === mb_substr($haystack, -mb_strlen($needle), null, 'UTF-8')) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === mb_substr($haystack, -mb_strlen($needle), null, 'UTF-8')) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        if (($segment = array_shift($segments)) === '*') {
            if (! is_array($target)) {
                $target = [];
            }
            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (is_array($target)) {
            if ($segments) {
                if (! array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! array_key_exists($segment, $target)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }
        return $target;
    }
}

if (! function_exists('array_only')) {
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

if (! function_exists('array_except')) {
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

if (! function_exists('camel_case')) {
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

if (! function_exists('array_camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    function array_camel_case($source)
    {
        $target = [];
        foreach ($source as $key => $value) {
            $newKey = is_numeric($key) ? $key : camel_case($key);
            if (is_array($value)) {
                $value = array_camel_case($value);
            }
            $target[$newKey] = $value;
        }
        return $target;
    }
}

if (! function_exists('camel_case')) {
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

if (! function_exists('array_camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    function array_camel_case($source)
    {
        $target = [];
        foreach ($source as $key => $value) {
            $newKey = is_numeric($key) || isset($ignoredKeys[$key]) ? $key : camel_case($key);
            if (is_array($value)) {
                $value = array_camel_case($value, $ignoredKeys);
            }
            $target[$newKey] = $value;
        }
        return $target;
    }
}

if (! function_exists('snake_case')) {
    /**
     * Convert a value to snake case.
     *
     * @param  string  $value
     * @return string
     */
    function snake_case($value)
    {
        return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1_', $value));
    }
}

if (! function_exists('array_snake_case')) {
    /**
     * Convert a value to snake case.
     *
     * @param  string  $value
     * @return string
     */
    function array_snake_case($source, $ignoredKeys = [])
    {
        $target = [];
        foreach ($source as $key => $value) {
            $newKey = is_numeric($key) || isset($ignoredKeys[$key]) ? $key : snake_case($key);
            if (is_array($value)) {
                $value = array_snake_case($value, $ignoredKeys);
            }
            $target[$newKey] = $value;
        }
        return $target;
    }
}

if (! function_exists('quote_sql')) {
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
        $isRaw = preg_match('/[_\w]+\([^)]+\)/', $value) || preg_match('/ AS /i', $value);

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
