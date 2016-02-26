<?php

use Mini\Entity\DatabaseSeed\DatabaseSeeder;

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
        require_once __TEST_DIRECTORY__ . '/FakeConnectionManager.php';

        $connectionManager = new FakeConnectionManager;

        $seeder = new DatabaseSeeder(__TEST_DIRECTORY__ . '/stubs/seeds', 'test');
        $seeder->connectionManager = $connectionManager;
        $seeder->execute();

        $this->assertEquals(
            [
                [
                    'default',
                    'REPLACE INTO users (id, guid, first_name, last_name) VALUES (?, ?, ?, ?)',
                    [1, '92e84436-62e1-4b04-9df4-9485cbe59a8d', 'Jonh', 'Doe']
                ],
                [
                    'default',
                    'DELETE FROM users WHERE id NOT IN (1)',
                    []
                ],
            ],
            $connectionManager->log
        );
    }
}
