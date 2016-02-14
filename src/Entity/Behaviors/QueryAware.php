<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 13/02/16
 * Time: 16:40
 */

namespace Mini\Entity\Behaviors;


use Mini\Entity\Model;

trait QueryAware
{

    /**
     * @var
     */
    private static $instanceTable;

    private static $instanceIdAttribute = "id";

    private static $model;

    /**
     * @param mixed $instanceTable
     */
    public static function instance()
    {
        $class = self::class;
        $obj = new $class;
        self::$instanceTable = $obj->getTable();
        if (isset($obj->idAttribute)) self::$instanceIdAttribute = $obj->idAttribute;

        self::$model = new Model();
    }

    /**
     * @param $id
     */
    public static function find($id, $columns = ['*']) {
        self::instance();

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id)
        );
        $result = self::$model->select($sql);
        return $result;
    }

    public static function findAll($columns = ['*']) {
        self::instance();

        $sql = sprintf(
            "SELECT %s FROM %s",
            implode(", ", $columns),
            self::$instanceTable
        );
        $result = self::$model->select($sql);
        return $result;
    }

    public static function destroy($id) {
        self::instance();

        $sql = sprintf(
            "DELETE FROM %s WHERE %s = %d",
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id)
        );

        return self::$model->execute($sql);
    }

}