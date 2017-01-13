<?php

use Mini\Entity\DataMapper;
use Mini\Entity\RawValue;
use Mini\Helpers\Fake\FakeConnectionManager;

class DataMapperTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/SoftDeleteEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/DataMapperStub.php';

        $this->connectionManager = new FakeConnectionManager;

        app()->register('Mini\Entity\ConnectionManager', function () {
            return $this->connectionManager;
        });
    }

    public function testIsCreating()
    {
        $entity = new EntityStub;
        $entity->name = 'Hi';

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users (name) VALUES (?)',
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

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET name = ? WHERE id = ?',
                    ['Test', 2]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsCreatingOrUpdating()
    {
        $entity = new EntityStub;
        $entity->name = 'Hi';

        $mapper = new DataMapperStub;
        $mapper->createOrUpdate($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users (name) VALUES (?) ON DUPLICATE KEY UPDATE name = ?',
                    ['Hi', 'Hi']
                ],
            ],
            $this->connectionManager->log
        );
        $this->assertEquals(1, $entity->id);
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

    public function testIsConvertingBooleanToInteger()
    {
        $mapper = new DataMapperStub;
        $mapper->updateByFilters(
            new EntityStub,
            [
                'is_draft' => true,
                'is_active' => false
            ],
            [
                'id' => 311,
            ]
        );

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET is_draft = ?, is_active = ? WHERE id = ?',
                    [1, 0, 311]
                ],
            ],
            $this->connectionManager->log
        );
    }
}
