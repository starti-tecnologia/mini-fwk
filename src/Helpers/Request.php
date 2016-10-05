<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 13/02/16
 * Time: 19:54
 */

namespace Mini\Helpers;


use Mini\Exceptions\MiniException;

class Request extends RequestBase
{

    /**
     * @var
     */
    private $data;

    private static $currentInstance;

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    public function get($string) {
        $value = array_get($this->data, $string);
        if ($value === null)
            return $this->getValueDefaultMethods($string);

        return $value;
    }

    private function getValueDefaultMethods($string) {
        if (isset($_GET[$string])) return $_GET[$string];
        else if (isset($_POST[$string])) return $_POST[$string];
        else if (isset($_FILES[$string])) return $_FILES[$string];
        return null;
    }

    /**
     * Instance a new Request object
     *
     * @return Request
     */
    public static function instance() {
        if (! self::$currentInstance) {
            $dataParsed = static::parse();
            self::$currentInstance = new Request();
            self::$currentInstance->setData($dataParsed);
        }
        return self::$currentInstance;
    }

    /**
     * Return json object
     *
     * @return mixed
     */
    public function getJSON() {
        return $this->data;
    }

}
