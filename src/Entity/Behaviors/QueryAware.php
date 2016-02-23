<?php

namespace Mini\Entity\Behaviors;

use Mini\Exceptions\MiniException;
use Mini\Container;

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
     * @var bool
     */
    private static $instanceUseSoftDeletes = false;

    /**
     * @var
     */
    private static $instanceConnection;

    /**
     *
     */
    public static function instance()
    {
        $class = self::class;
        $obj = new $class;
        self::$instanceTable = $obj->table;
        if (isset($obj->idAttribute)) self::$instanceIdAttribute = $obj->idAttribute;
        self::$instanceUseSoftDeletes = $obj->useSoftDeletes;

        self::$instanceConnection = app()->get('Mini\Entity\ConnectionManager')->getConnection($obj->connection);
    }

    /**
     * @param $id
     */
    public static function find($id, $columns = ['*']) {
        self::instance();

        if (!self::$instanceUseSoftDeletes)
            $where_soft_delete = "";
        else
            $where_soft_delete = " AND (deleted_at IS NULL)";

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d %s",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id),
            $where_soft_delete
        );
        $result = self::$instanceConnection->select($sql);
        return $result;
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public static function findOne($id, $columns = ['*']) {
        self::instance();

        if (!self::$instanceUseSoftDeletes)
            $where_soft_delete = "";
        else
            $where_soft_delete = " AND (deleted_at IS NULL)";

        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d %s",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id),
            $where_soft_delete
        );
        $result = self::$instanceConnection->select($sql);
        return $result[0];
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public static function findAll($columns = ['*']) {
        self::instance();

        if (!self::$instanceUseSoftDeletes)
            $where_soft_delete = "";
        else
            $where_soft_delete = " WHERE (deleted_at IS NULL)";

        $sql = sprintf(
            "SELECT %s FROM %s %s",
            implode(", ", $columns),
            self::$instanceTable,
            $where_soft_delete
        );
        $result = self::$instanceConnection->select($sql);
        return $result;
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function destroy($id) {
        self::instance();

        if (!self::$instanceUseSoftDeletes) {
            $sql = sprintf(
                "DELETE FROM %s WHERE %s = %d",
                self::$instanceTable,
                self::$instanceIdAttribute,
                intval($id)
            );
        } else {
            $sql = sprintf(
                "UPDATE %s SET deleted_at = NOW() WHERE %s = %d",
                self::$instanceTable,
                self::$instanceIdAttribute,
                intval($id)
            );
        }

        return self::$instanceConnection->exec($sql);
    }

    /**
     * @param $fields
     * @param array $orderBy
     * @param array $columns
     * @return mixed
     * @throws MiniException
     */
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

        if (self::$instanceUseSoftDeletes)
            $where[] = "(deleted_at IS NULL)";

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

        return self::$instanceConnection->select($sql);
    }

}