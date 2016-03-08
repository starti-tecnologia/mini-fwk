<?php

use Mini\Entity\Migration\Table;
use Mini\Entity\Migration\TableItem;

class TableTest extends PHPUnit_Framework_TestCase
{
    public function testIsCreatingColumnAddOperations()
    {
        $currentTable = new Table;
        $currentTable->name = 'users';
        $currentTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_COLUMN, 'name', 'name varchar(255)'),
        ];

        $databaseTable = new Table;
        $databaseTable->name = 'users';
        $databaseTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
        ];

        $operations = $currentTable->makeAddOperations($databaseTable);

        $this->assertEquals(
            [
                'ALTER TABLE users ADD COLUMN name varchar(255)'
            ],
            $operations
        );
    }

    public function testIsCreatingUniqueAddOperations()
    {
        $currentTable = new Table;
        $currentTable->name = 'users';
        $currentTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_CONSTRAINT, 'guid_unique', 'CREATE UNIQUE INDEX users_guid_unique ON users (guid)'),
        ];

        $databaseTable = new Table;
        $databaseTable->name = 'users';
        $databaseTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
        ];

        $operations = $currentTable->makeAddOperations($databaseTable);

        $this->assertEquals(
            [
                'CREATE UNIQUE INDEX users_guid_unique ON users (guid)'
            ],
            $operations
        );
    }

    public function testIsCreatingColumnModifyOperations()
    {
        $currentTable = new Table;
        $currentTable->name = 'users';
        $currentTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_COLUMN, 'name', 'name varchar(40)'),
        ];

        $databaseTable = new Table;
        $databaseTable->name = 'users';
        $databaseTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_COLUMN, 'name', 'name varchar(255)'),
        ];

        $operations = $currentTable->makeModifyOperations($databaseTable);

        $this->assertEquals(
            [
                'ALTER TABLE users MODIFY COLUMN name varchar(40)'
            ],
            $operations
        );
    }

    public function testIsCreatingUniqueConstraintModifyOperations()
    {
        $currentTable = new Table;
        $currentTable->name = 'users';
        $currentTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_CONSTRAINT, 'users_guid_unique', 'CREATE UNIQUE INDEX users_guid_unique ON users (guid)'),
        ];

        $databaseTable = new Table;
        $databaseTable->name = 'users';
        $databaseTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_CONSTRAINT, 'users_guid_unique', 'CREATE UNIQUE INDEX users_guid_unique ON users (id)'),
        ];

        $operations = $currentTable->makeModifyOperations($databaseTable);

        $this->assertEquals(
            [
                'ALTER TABLE users DROP INDEX users_guid_unique;CREATE UNIQUE INDEX users_guid_unique ON users (guid)'
            ],
            $operations
        );
    }

    public function testIsCreatingColumnDropOperations()
    {
        $currentTable = new Table;
        $currentTable->name = 'users';
        $currentTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
        ];

        $databaseTable = new Table;
        $databaseTable->name = 'users';
        $databaseTable->items = [
            new TableItem(TableItem::TYPE_COLUMN, 'id', 'id integer(11) unsigned auto_increment'),
            new TableItem(TableItem::TYPE_COLUMN, 'name', 'name varchar(255)'),
        ];

        $operations = $currentTable->makeDropOperations($databaseTable);

        $this->assertEquals(
            [
                'ALTER TABLE users DROP COLUMN name'
            ],
            $operations
        );
    }
}
