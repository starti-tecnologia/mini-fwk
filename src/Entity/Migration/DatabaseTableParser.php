<?php

namespace Mini\Entity\Migration;

use Mini\Entity\Entity;
use Mini\Entity\Definition\DefinitionParser;
use Mini\Entity\Migration\Table;
use Mini\Entity\Migration\TableItem;
use Mini\Entity\Connection;

class DatabaseTableParser
{
    /**
     * @var Mini\Entity\Connection
     */
    private $connection;

    public function __construct()
    {
        $this->tableSorter = new TableSorter;
    }

    public function parse()
    {
        $result = [];

        foreach ($this->findTables() as $row) {
            $result[$row['TABLE_NAME']] = $this->parseTable($row['TABLE_NAME']);
        }

        return $this->tableSorter->sort($result);
    }

    public function parseTable($tableName)
    {
        $columns = array_map(function ($row) {
            return $this->parseColumn($row);
        }, $this->findTableColumns($tableName));

        $constraints = array_filter(array_map(function ($row) {
            return $this->parseConstraint($row);
        }, $this->findTableConstraints($tableName)));

        if (count($columns)) {
            $table = new Table($tableName);
            foreach (array_merge($columns, $constraints) as $item) {
                $table->items[$item->name] = $item;
            }
            return $table;
        }
    }

    public function findTables()
    {
        return $this->connection->select('
            SELECT
                TABLE_NAME
            FROM
                INFORMATION_SCHEMA.TABLES
            WHERE
                TABLE_SCHEMA = :schemaName AND
                TABLE_COMMENT = \'MINI_FWK_ENTITY\'
        ', [
            'schemaName' => $this->connection->database
        ]);
    }

    public function findTableConstraints($tableName)
    {
        return $this->connection->select('
            SELECT
                C.CONSTRAINT_SCHEMA,
                C.CONSTRAINT_NAME,
                C.TABLE_SCHEMA,
                C.TABLE_NAME,
                C.CONSTRAINT_TYPE,
                K.COLUMN_NAME,
                K.REFERENCED_TABLE_NAME,
                K.REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.TABLE_CONSTRAINTS C
                LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE K ON (
                    K.TABLE_SCHEMA = C.TABLE_SCHEMA AND
                    K.TABLE_NAME = C.TABLE_NAME AND
                    K.CONSTRAINT_NAME = C.CONSTRAINT_NAME
                )
            WHERE
                C.TABLE_NAME = :tableName AND
                C.TABLE_SCHEMA = :schemaName
        ', [
            'tableName' => $tableName,
            'schemaName' => $this->connection->database
        ]);
    }

    public function findTableColumns($tableName)
    {
        return $this->connection->select('
            SELECT
                TABLE_SCHEMA,
                TABLE_NAME,
                COLUMN_NAME,
                COLUMN_DEFAULT,
                IS_NULLABLE,
                DATA_TYPE,
                CHARACTER_MAXIMUM_LENGTH,
                CHARACTER_OCTET_LENGTH,
                NUMERIC_PRECISION,
                NUMERIC_SCALE,
                CHARACTER_SET_NAME,
                COLLATION_NAME,
                COLUMN_TYPE,
                COLUMN_KEY,
                EXTRA,
                PRIVILEGES,
                COLUMN_COMMENT
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_NAME = :tableName AND
                TABLE_SCHEMA = :schemaName
        ', [
            'tableName' => $tableName,
            'schemaName' => $this->connection->database
        ]);
    }

    /**
     * Row Example:
     * 
     * TABLE_CATALOG: def
     * TABLE_SCHEMA: hplus
     * TABLE_NAME: users
     * COLUMN_NAME: document2
     * ORDINAL_POSITION: 20
     * COLUMN_DEFAULT: NULL
     * IS_NULLABLE: YES
     * DATA_TYPE: varchar
     * CHARACTER_MAXIMUM_LENGTH: 255
     * CHARACTER_OCTET_LENGTH: 765
     * NUMERIC_PRECISION: NULL
     * NUMERIC_SCALE: NULL
     * DATETIME_PRECISION: NULL
     * CHARACTER_SET_NAME: utf8
     * COLLATION_NAME: utf8_unicode_ci
     * COLUMN_TYPE: varchar(255)
     * COLUMN_KEY: 
     * EXTRA: 
     * PRIVILEGES: select,insert,update,references
     * COLUMN_COMMENT:
     */
    public function parseColumn(array $row)
    {
        $sql = $row['COLUMN_NAME'] . ' ' . $row['COLUMN_TYPE'] .
            ($row['IS_NULLABLE'] == 'NO' ? ' not null' : '') .
            ($row['COLUMN_DEFAULT'] !== null ? ' default ' . $row['COLUMN_DEFAULT'] : '') .
            (stristr($row['COLUMN_KEY'], 'PRI') ? ' primary key' : '') .
            (stristr($row['EXTRA'], 'auto_increment') ? ' auto_increment' : '');

        return new TableItem(
            TableItem::TYPE_COLUMN,
            $row['COLUMN_NAME'],
            $sql
        );
    }

    /**
     * Row Example:
     *
     * CONSTRAINT_SCHEMA: laravel
     * CONSTRAINT_NAME: PRIMARY
     * TABLE_SCHEMA: laravel
     * TABLE_NAME: users
     * CONSTRAINT_TYPE: PRIMARY KEY
     */
    public function parseConstraint(array $row)
    {
        $sql = null;

        if ($row['CONSTRAINT_TYPE'] == 'UNIQUE') {
            $sql = sprintf(
                'CREATE UNIQUE INDEX %s ON %s (%s)',
                $row['CONSTRAINT_NAME'], $row['TABLE_NAME'], $row['COLUMN_NAME']
            );
        } elseif ($row['CONSTRAINT_TYPE'] == 'FOREIGN KEY') {
            $sql = sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
                $row['TABLE_NAME'], $row['CONSTRAINT_NAME'], $row['COLUMN_NAME'], $row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME']
            );
        }

        if ($sql) {
            return new TableItem(
                TableItem::TYPE_CONSTRAINT,
                $row['CONSTRAINT_NAME'],
                $sql
            );
        }
    }

    /**
     * @params \Mini\Entity\Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
