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
        require_once __TEST_DIRECTORY__ . '/stubs/CustomFieldStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/MultipleColumnPrimaryKeyStub.php';

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

    public function testIsCreatingWithTimestamp()
    {
        $entity = new CustomFieldStub;
        $entity->name = 'Hi';

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users (name, data_cadastro, data_atualizacao) VALUES (?, NOW(), NOW())',
                    ['Hi']
                ],
            ],
            $this->connectionManager->log
        );
        $this->assertEquals(1, $entity->id);
    }

    public function testIsSavingMultipleColumnPrimaryKey()
    {
        $entity = new MultipleColumnPrimaryKeyStub;
        $entity->user_id = 2;
        $entity->group_id = 1;
        $entity->active = 0;

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users_groups (user_id, group_id, active) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE user_id = ?, group_id = ?, active = ?',
                    [2, 1, 0, 2, 1, 0]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsSavingMultipleColumnPrimaryKeyWithoutFields()
    {
        $entity = new MultipleColumnPrimaryKeyStub;
        $entity->user_id = 2;
        $entity->group_id = 1;

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'INSERT INTO users_groups (user_id, group_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = ?, group_id = ?',
                    [2, 1, 2, 1]
                ],
            ],
            $this->connectionManager->log
        );
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

    public function testIsUpdatingWithTimestamp()
    {
        $entity = new CustomFieldStub;
        $entity->id = 2;
        $entity->name = 'Test';

        $mapper = new DataMapperStub;
        $mapper->save($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET name = ?, data_atualizacao = NOW() WHERE id = ?',
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
                    'INSERT INTO users (name) VALUES (?) ON DUPLICATE KEY UPDATE name = ?, id = LAST_INSERT_ID(id)',
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

    public function testIsSoftDeletingCustomField()
    {
        $entity = new CustomFieldStub;
        $entity->id = 2;

        $mapper = new DataMapperStub;
        $mapper->delete($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'UPDATE users SET inativo = 1 WHERE id = ?',
                    [2]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsDeletingMultipleColumnPrimaryKey()
    {
        $entity = new MultipleColumnPrimaryKeyStub;
        $entity->user_id = 1;
        $entity->group_id = 2;

        $mapper = new DataMapperStub;
        $mapper->delete($entity);

        $this->assertEquals(
            [
                [
                    'default',
                    'DELETE FROM users_groups WHERE user_id = ? AND group_id = ?',
                    [1, 2]
                ],
            ],
            $this->connectionManager->log
        );
    }

    public function testIsCallingDeleteHooks()
    {
        $entity = new CustomFieldStub;
        $entity->id = 2;

        $mapper = new DataMapperStub;
        $mapper->delete($entity);

        $this->assertEquals(
            ['before_delete', 'after_delete'],
            $mapper->calledHooks
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
