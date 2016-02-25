<?php
namespace Mini\Helpers;

class Header
{

    /**
     * @return array
     */
    private static function headers() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } else if ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } else if ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }
        return $headers;
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key) {
        $headers = self::headers();
        return isset($headers[$key]) ? $headers[$key] : null;
    }

}