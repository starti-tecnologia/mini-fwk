<?php

use Mini\Entity\Entity;
use Mini\Entity\EntitySerializer;

/**
 * @todo Remove duplicate code from OutputSerializer
 */
class EntitySerializerTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SerializableEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';
    }

    public function testIsSerializingEntities()
    {
        $entity = new SerializableEntityStub;

        $entity->fields = [
            'id' => '1',
            'name' => 'Lala',
            'is_draft' => '1',
            'max_users_quantity' => '10',
            'address_street_name' => 'Lala Street',
            'address_number' => '1C',
            'owner_id' => '1',
            'owner_will_go' => '1',
            'owner_name' => 'John'
        ];

        $this->assertEquals(
            [
                'id' => 1,
                'name' => 'Lala',
                'is_draft' => true,
                'max_users_quantity' => 10,
                'address' => [
                    'street_name' => 'Lala Street',
                    'number' => '1C'
                ],
                'owner_will_go' => true,
                'owner' => [
                    'id' => 1,
                    'name' => 'John',
                ]
            ],
            EntitySerializer::instance()->serialize($entity)
        );
    }

    public function testIsSerializingEntitiesWithCamelCase()
    {
        putenv('CONVERT_CAMEL_CASE=1');

        $entity = new SerializableEntityStub;

        $entity->fields = [
            'id' => '1',
            'name' => 'Lala',
            'is_draft' => null,
            'max_users_quantity' => '10',
            'address_street_name' => 'Lala Street',
            'address_number' => '1C',
            'owner_id' => '1',
            'owner_name' => 'John',
            'owner_will_go' => '1',
        ];

        $this->assertEquals(
            [
                'id' => 1,
                'name' => 'Lala',
                'isDraft' => null,
                'maxUsersQuantity' => 10,
                'ownerWillGo' => true,
                'address' => [
                    'streetName' => 'Lala Street',
                    'number' => '1C'
                ],
                'owner' => [
                    'id' => 1,
                    'name' => 'John'
                ]
            ],
            EntitySerializer::instance()->serialize($entity)
        );

        putenv('CONVERT_CAMEL_CASE=0');
    }

    public function testIsSerializingRelations()
    {
        $entity = new RelationEntityStub;

        $entity->fields = [
            'id' => '1',
            'name' => 'Lala',
            'owner_id' => '1',
            'owner_name' => 'John',
        ];

        $this->assertEquals(
            [
                'id' => 1,
                'name' => 'Lala',
                'owner' => [
                    'id' => 1,
                    'name' => 'John'
                ]
            ],
            EntitySerializer::instance()->serialize($entity)
        );
    }
}
