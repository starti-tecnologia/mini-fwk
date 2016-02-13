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

    /**
     * @var
     */
    private static $instanceTable;

    private static $instanceIdAttribute = "id";

    /**
     * @param mixed $instanceTable
     */
    public static function instanceTable()
    {
        $class = self::class;
        $obj = new $class;
        self::$instanceTable = $obj->getTable();
        if (isset($obj->idAttribute)) self::$instanceIdAttribute = $obj->idAttribute;
    }

    /**
     * @param $id
     */
    public static function find($id, $columns = ['*']) {
        self::instanceTable();

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id)
        );
        $result = parent::select($sql);
        return $result;
    }

}