<?php

namespace Mini\Entity\Behaviors;
use Mini\Entity\Model;
use Mini\Exceptions\MiniException;

/**
 * Trait QueryAware
 * @package Mini\Entity\Behaviors
 */
trait QueryAware
{

    /**
     * @var
     */
    private static $instanceTable;

    /**
     * @var string
     */
    private static $instanceIdAttribute = "id";

    /**
     * @var
     */
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

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public static function findOne($id, $columns = ['*']) {
        self::instance();

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id)
        );
        $result = self::$model->select($sql);
        return $result[0];
    }

    /**
     * @param array $columns
     * @return mixed
     */
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

    /**
     * @param $id
     * @return mixed
     */
    public static function destroy($id) {
        self::instance();

        $sql = sprintf(
            "DELETE FROM %s WHERE %s = %d",
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id)
        );

        return self::$model->exec($sql);
    }

    public static function where($fields, $orderBy = [], $columns = ['*']) {
        self::instance();

        if (count($fields) == 0)
            throw new MiniException("Fields array is empty.");

        $where = [];
        foreach ($fields as $field => $value) {
            $where[] = sprintf(
                "%s = '%s'",
                $field,
                $value
            );
        }

        if (count($orderBy) > 0) {
            $orders = [];
            foreach ($orderBy as $order => $dir) {
                $orders[] = sprintf(
                    "%s %s",
                    $order,
                    $dir
                );
            }
            $orders = sprintf(
                "ORDER BY %s",
                implode(", ", $orders)
            );
        } else $orders = "";

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s %s",
            implode(", ", $columns),
            self::$instanceTable,
            implode(" AND ", $where),
            $orders
        );

        return self::$model->select($sql);
    }

}