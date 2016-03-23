<?php

use Mini\Entity\Migration\DatabaseTableParser;

class DatabaseTableParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * TODO: Parse index and full text indexes
     */
    public function testIsParsingColumn()
    {
        $parser = new DatabaseTableParser;

        $item = $parser->parseColumn([
            'TABLE_SCHEMA' => 'test',
            'TABLE_NAME' => 'lala',
            'COLUMN_NAME' => 'test2',
            'COLUMN_DEFAULT' => '0',
            'IS_NULLABLE' => 'NO',
            'COLUMN_TYPE' => 'int(11)',
            'COLUMN_KEY' => '',
            'EXTRA' => ''
        ]);

        $this->assertEquals('test2', $item->name);
        $this->assertEquals('test2 int(11) not null default 0', $item->sql);
    }

    public function testIsParsingEmptyDefault()
    {
        $parser = new DatabaseTableParser;

        $item = $parser->parseColumn([
            'TABLE_SCHEMA' => 'test',
            'TABLE_NAME' => 'lala',
            'COLUMN_NAME' => 'test2',
            'COLUMN_DEFAULT' => '',
            'IS_NULLABLE' => 'NO',
            'COLUMN_TYPE' => 'int(11)',
            'COLUMN_KEY' => '',
            'EXTRA' => ''
        ]);

        $this->assertEquals('test2', $item->name);
        $this->assertEquals('test2 int(11) not null default \'\'', $item->sql);
    }

    public function testIsParsingDecimalDefault()
    {
        $parser = new DatabaseTableParser;

        $item = $parser->parseColumn([
            'TABLE_SCHEMA' => 'test',
            'TABLE_NAME' => 'lala',
            'COLUMN_NAME' => 'test2',
            'COLUMN_DEFAULT' => '0.00',
            'IS_NULLABLE' => 'NO',
            'COLUMN_TYPE' => 'int(11)',
            'COLUMN_KEY' => '',
            'EXTRA' => ''
        ]);

        $this->assertEquals('test2', $item->name);
        $this->assertEquals('test2 int(11) not null default 0', $item->sql);
    }
}
