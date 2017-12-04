<?php

namespace Mini\Entity\Mongo\Behaviors;

use MongoDB\Driver\Query;
use MongoDB\Driver\Cursor;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\BulkWrite;
use Mini\Entity\Mongo\Query as QueryBuilder;

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
    public static function findAll($columns = [], $options = []) {
        self::instance();

        $queryOptions = [];
        if (count($columns) > 0) {
            $columns = array_map(function ($value) {
                return 1;
            }, array_flip($columns));
            $queryOptions['projection'] = $columns;
        }
        if (count($options) > 0) {
          $queryOptions = array_merge($queryOptions, $options);
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
    public static function find($query, $columns = [], $options = []) {
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
        if (count($options) > 0) {
          $queryOptions = array_merge($queryOptions, $options);
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
     * @param array $query
     * @param array $data
     */
    public static function update(array $query, array $data, array $options = []) {
        self::instance();

        $bulk = new BulkWrite;
        $bulk->update($query, $data, $options);

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
            $exec = self::$instanceConnection
                ->executeBulkWrite(self::$instanceNamespace, self::$bulkWriteInsert);

            self::$bulkWriteInsert = null;

            return $exec;
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
                } else if ($value instanceof UTCDateTime) {
                    $datetime = $value->toDateTime();
                    $value = $datetime->format(\DateTime::ATOM);
                }

                $array[$i][$key] = $value;
            }
            ++$i;
        }
        return $array;
    }

    public static function q()
    {
        self::instance();
        $query = new QueryBuilder(self::$instanceNamespace, self::$instanceConnection);
        return $query;
    }

    public static function aggregate(array $pipeline)
    {
        self::instance();
        list($db, $collection) = explode('.', self::$instanceNamespace);
        $cursor = self::$instanceConnection->executeCommand(
            $db,
            new \MongoDB\Driver\Command([
                'aggregate' => $collection,
                'cursor' => (object) [],
                'pipeline' => $pipeline
            ])
        );
        $aggregation = json_decode(json_encode(iterator_to_array($cursor)), true);
        $rows = [];
        foreach ($aggregation as $row) {
            $rows[$row['_id']] = $row;
        }
        return $rows;
    }
}
