<?php

use Mini\Entity\DataMapper;
use Mini\Entity\RawValue;

class DataMapperTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/FakeConnectionManager.php';
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/SoftDeleteEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/DataMapperStub.php';

        $this->connectionManager = new FakeConnectionManager;
        DataMapper::$connectionMap = [];

        app()->register('Mini\Entity\ConnectionManager', function () {
            return $this->connectionManager;
        });
    }

    public function testIsCreating()
    {
        $entity = new EntityStub;
        $entity->lala = 'Hi';
        $entity->requested_at = new RawValue('NOW()');

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users (lala, requested_at) VALUES (?, NOW())',
                    ['Hi']
                ],
            ],
            $this->connectionManager->log
        );
        $this->assertEquals(1, $entity->id);
    }

    public function testIsUpdating()
    {
        $entity = new EntityStub;
        $entity->id = 2;
        $entity->name = 'Test';
        $entity->requested_at = new RawValue('NOW()');

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET name = ?, requested_at = NOW() WHERE id = ?',
                    ['Test', 2]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsDeleting()
    {
        $entity = new EntityStub;
        $entity->id = 2;

        $mapper = new DataMapperStub;
        $mapper->delete($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'DELETE FROM users WHERE id = ?',
                    [2]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsSoftDeleting()
    {
        $entity = new SoftDeleteEntityStub;
        $entity->id = 2;

        $mapper = new DataMapperStub;
        $mapper->delete($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET deleted_at = NOW() WHERE id = ?',
                    [2]
                ],
            ],
            $this->connectionManager->log
        );
    }
}