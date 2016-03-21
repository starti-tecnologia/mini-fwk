<?php

namespace Mini\Entity\Mongo\Behaviors;

use MongoDB\Driver\Query;
use MongoDB\Driver\Cursor;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\BulkWrite;

trait MongoQueryAware
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
     * @var \MongoDB\Driver\Manager
     */
    private static $instanceConnection;

    /**
     * @var
     */
    private static $instanceNamespace;

    /**
     * @var array
     */
    private static $bulkWriteInsert = null;

    /**
     *
     */
    public static function instance()
    {
        $obj = new self;
        self::$instanceTable = $obj->table;
        if (isset($obj->idAttribute)) self::$instanceIdAttribute = $obj->idAttribute;
        self::$instanceUseSoftDeletes = $obj->useSoftDeletes;

        self::$instanceConnection = app()->get('Mini\Entity\ConnectionManager')->getConnection($obj->connection);

        self::$instanceNamespace = sprintf(
            "%s.%s",
            self::$instanceConnection->getDbName(),
            self::$instanceTable
        );

        self::$instanceConnection = self::$instanceConnection->getDb();
    }

    /**
     * @param array $columns
     * @return array
     */
    public static function findAll($columns = []) {
        self::instance();

        $queryOptions = [];
        if (count($columns) > 0) {
            $columns = array_map(function ($value) {
                return 1;
            }, array_flip($columns));
            $queryOptions['projection'] = $columns;
        }

        $query = new Query(
            [],
            $queryOptions
        );
        $cursor = self::$instanceConnection
            ->executeQuery(self::$instanceNamespace, $query);

        return self::toArray($cursor);

    }

    /**
     * @param $query
     * @param array $columns
     * @return array
     */
    public static function find($query, $columns = []) {
        self::instance();

        if (isset($query['_id']) && ! $query['_id'] instanceof ObjectID) {
            $query['_id'] = new ObjectID($query['_id']);
        }

        $queryOptions = [];
        if (count($columns) > 0) {
            $columns = array_map(function ($value) {
                return 1;
            }, array_flip($columns));
            $queryOptions['projection'] = $columns;
        }

        $query = new Query($query, $queryOptions);

        $cursor = self::$instanceConnection
            ->executeQuery(self::$instanceNamespace, $query);

        return self::toArray($cursor);
    }

    /**
     * @param array $data
     */
    public static function store(array $data) {
        self::instance();

        $bulk = new BulkWrite;
        $bulk->insert($data);

        return self::$instanceConnection
            ->executeBulkWrite(self::$instanceNamespace, $bulk);
    }

    /**
     * @param array $data
     */
    public static function prepareBulkWrite(array $data) {
        if (self::$bulkWriteInsert === null) self::$bulkWriteInsert = new BulkWrite;

        self::$bulkWriteInsert->insert($data);
    }

    /**
     * @return \MongoDB\Driver\WriteResult|null
     */
    public static function execBulkWrite() {
        self::instance();

        if (self::$bulkWriteInsert !== null) {
            return self::$instanceConnection
                ->executeBulkWrite(self::$instanceNamespace, self::$bulkWriteInsert);
        } else return null;
    }

    /**
     * @param $id
     * @return \MongoDB\Driver\WriteResult
     */
    public static function destroy($id) {
        self::instance();

        if (! $id instanceof ObjectID) $id = new ObjectID($id);

        $bulk = new BulkWrite;
        $bulk->delete([
            '_id' => $id
        ]);

        return self::$instanceConnection
            ->executeBulkWrite(self::$instanceNamespace, $bulk);

    }

    /**
     * @param Cursor $cursor
     * @return array
     */
    private static function toArray(Cursor $cursor) {
        $array = [];
        $i = 0;
        foreach ($cursor as $item) {
            foreach ($item as $key => $value) {
                if ($value instanceof ObjectID) {
                    $value = $value->__toString();
                }

                $array[$i][$key] = $value;
            }
            ++$i;
        }
        return $array;
    }

}