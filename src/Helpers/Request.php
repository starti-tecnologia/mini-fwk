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
            //throw new MiniException(sprintf("The field '%s' not found", $string));

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
        $dataParsed = static::parse();

        $obj = new Request();
        $obj->setData($dataParsed);

        return $obj;
    }

}