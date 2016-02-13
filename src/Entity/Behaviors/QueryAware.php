<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 13/02/16
 * Time: 16:40
 */

namespace Mini\Entity\Behaviors;


trait QueryAware
{

    public static function find($id) {
        echo self::$table;
    }

}