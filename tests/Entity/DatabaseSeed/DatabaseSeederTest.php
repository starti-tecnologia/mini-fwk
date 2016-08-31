<?php

use Mini\Entity\DatabaseSeed\DatabaseSeeder;
use Mini\Helpers\Fake\FakeConnectionManager;

class DatabaseSeederTest extends PHPUnit_Framework_TestCase
{
    public function testIsLoadingData()
    {
        $seeder = new DatabaseSeeder(__TEST_DIRECTORY__ . '/stubs/seeds', 'test');
        $seeder->loadData();

        $this->assertEquals(
            [
                'users' => [
                    'connection' => 'default',

                    'rows' => [
                        [
                            'id' => 1,
                            'guid' => '92e84436-62e1-4b04-9df4-9485cbe59a8d',
                            'first_name' => 'Jonh',
                            'last_name' => 'Doe'
                        ]
                    ]
                ]
            ],
            $seeder->data
        );
    }

    public function testIsExecuting()
    {
        $fixtures = [
            '/information_schema/' => [['column_name' => 'id', 'table_name' => 'users']]
        ];

        $connectionManager = new FakeConnectionManager($fixtures);

        $seeder = new DatabaseSeeder(__TEST_DIRECTORY__ . '/stubs/seeds', 'test');
        $seeder->connectionManager = $connectionManager;
        $seeder->execute();

        $this->assertEquals(
            [
                [
                    'default',
                    "SELECT k.column_name, t.table_name FROM information_schema.table_constraints t JOIN information_schema.key_column_usage k USING(constraint_name,table_schema,table_name) WHERE t.constraint_type='PRIMARY KEY' AND t.table_schema=?",
                    ['test']
                ],
                [
                    'default',
                    'SET foreign_key_checks = 0;',
                    []
                ],
                [
                    'default',
                    'REPLACE INTO users (id, guid, first_name, last_name) VALUES (?, ?, ?, ?)',
                    [1, '92e84436-62e1-4b04-9df4-9485cbe59a8d', 'Jonh', 'Doe']
                ],
                [
                    'default',
                    'DELETE FROM users WHERE (id) NOT IN ((1))',
                    []
                ],
                [
                    'default',
                    'SET foreign_key_checks = 1;',
                    []
                ],
            ],
            $connectionManager->log
        );
    }
}
