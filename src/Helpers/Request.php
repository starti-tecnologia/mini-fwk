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
        if (isset($this->data[$string]))
            throw new MiniException(sprintf("The field '%s' not found", $string), null, null);

        return $this->data[$string];
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