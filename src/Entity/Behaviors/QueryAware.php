<?php

namespace Mini\Entity\Behaviors;

use Mini\Entity\Connection;
use Mini\Exceptions\MiniException;
use Mini\Container;
use Mini\Entity\Query;
use Mini\Entity\RawValue;

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
    private static $instanceIdAttribute = 'id';

    /**
     * @var string
     */
    public static $instanceCreatedAttribute = 'created_at';

    /**
     * @var string
     */
    public static $instanceUpdatedAttribute = 'updated_at';

    /**
     * Set the updated attribute to be set when creating
     *
     * @var string
     */
    public static $instanceUpdatedAttributeRequired = false;

    /**
     * @var string
     */
    public static $instanceDeletedAttribute = 'deleted_at';

    /**
     * @var string
     */
    public static $instanceDeletedType = 'datetime';

    /**
     * @var bool
     */
    private static $instanceUseSoftDeletes = false;

    /**
     * @var Connection
     */
    private static $instanceConnectionName;

    /**
     *
     */
    public static function instance()
    {
        $obj = new self;
        self::$instanceTable = $obj->table;
        if (isset($obj->idAttribute)) self::$instanceIdAttribute = $obj->idAttribute;
        self::$instanceUseSoftDeletes = $obj->useSoftDeletes;
        self::$instanceCreatedAttribute = $obj->createdAttribute;
        self::$instanceUpdatedAttribute = $obj->updatedAttribute;
        self::$instanceUpdatedAttributeRequired = $obj->updatedAttributeRequired;
        self::$instanceDeletedAttribute = $obj->deletedAttribute;
        self::$instanceDeletedType = $obj->deletedType;

        self::$instanceConnectionName = $obj->connection;
    }

    public static function getInstanceConnection()
    {
        return app()->get('Mini\Entity\ConnectionManager')->getConnection(self::$instanceConnectionName);
    }

    private static function getWhereSoftDelete($includeAnd = true)
    {
        $deletedAttribute = self::$instanceDeletedAttribute;
        if (!self::$instanceUseSoftDeletes) {
            $where_soft_delete = '';
        } elseif (self::$instanceDeletedType == 'datetime') {
            $where_soft_delete = "($deletedAttribute IS NULL)";
        } else {
            $where_soft_delete = "($deletedAttribute = 0)";
        }
        if ($where_soft_delete && $includeAnd) {
            $where_soft_delete = ' AND ' . $where_soft_delete;
        }
        return $where_soft_delete;
    }

    private static function getSetSoftDelete()
    {
        $deletedAttribute = self::$instanceDeletedAttribute;
        if (!self::$instanceUseSoftDeletes) {
            $setSoftDelete = '';
        } elseif (self::$instanceDeletedType == 'datetime') {
            $setSoftDelete = "$deletedAttribute = NOW()";
        } else {
            $setSoftDelete = "$deletedAttribute = 1";
        }
        return $setSoftDelete;
    }

    /**
     * @param $id
     */
    public static function find($id, $columns = ['*']) {
        self::instance();
        $where_soft_delete = self::getWhereSoftDelete();
        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d %s",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id),
            $where_soft_delete
        );
        $result = self::getInstanceConnection()->select($sql);
        return $result;
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public static function findOne($id, $columns = ['*']) {
        self::instance();
        $where_soft_delete = self::getWhereSoftDelete();
        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s = %d %s",
            implode(", ", $columns),
            self::$instanceTable,
            self::$instanceIdAttribute,
            intval($id),
            $where_soft_delete
        );
        $result = self::getInstanceConnection()->select($sql);
        return $result[0];
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public static function findAll($columns = ['*']) {
        self::instance();
        $where_soft_delete = self::getWhereSoftDelete(false);
        $sql = sprintf(
            "SELECT %s FROM %s %s",
            implode(", ", $columns),
            self::$instanceTable,
            $where_soft_delete ? 'WHERE ' . $where_soft_delete : ''
        );
        $result = self::getInstanceConnection()->select($sql);
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
            $setSoftDelete = self::getSetSoftDelete();
            $sql = sprintf(
                "UPDATE %s SET $setSoftDelete WHERE %s = %d",
                self::$instanceTable,
                self::$instanceIdAttribute,
                intval($id)
            );
        }

        return self::getInstanceConnection()->exec($sql);
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

        if (self::$instanceUseSoftDeletes) {
            $where[] = self::getWhereSoftDelete(false);
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

        return self::getInstanceConnection()->select($sql);
    }

    public static function select($sql, $params = [])
    {
        self::instance();
        return self::getInstanceConnection()->select($sql, $params);
    }

    public static function exec($sql)
    {
        self::instance();
        return self::getInstanceConnection()->exec($sql);
    }

    public static function query($ignoreDefault = false)
    {
        self::instance();

        $query = (new Query)
            ->table(self::$instanceTable)
            ->connection(self::getInstanceConnection())
            ->className(get_called_class());

        if (self::$instanceUseSoftDeletes && ! $ignoreDefault) {
            $deletedAttribute = self::$instanceTable . '.' . self::$instanceDeletedAttribute;
            if (self::$instanceDeletedType == 'datetime') {
                $query->whereIsNull($deletedAttribute);
            } else {
                $query->where($deletedAttribute, '=', new RawValue('0'));
            }
        }

        return $query;
    }

    /**
     * @return Query
     */
    public static function q($ignoreDefault = false)
    {
        return self::query($ignoreDefault);
    }
}
