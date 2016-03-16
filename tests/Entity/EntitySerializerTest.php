<?php

use Mini\Entity\Entity;
use Mini\Entity\EntitySerializer;

/**
 * @todo Test relation serializing
 * @todo Test performance
 * @todo Remove duplicate code from OutputSerializer
 */
class EntitySerializerTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SerializableEntityStub.php';
    }

    public function testIsSerializing()
    {
        $entity = new SerializableEntityStub;

        $entity->fields = [
            'id' => '1',
            'name' => 'Lala',
            'is_draft' => '1',
            'max_users_quantity' => '10',
            'address_street_name' => 'Lala Street',
            'address_number' => '1C',
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
                ]
            ],
            EntitySerializer::instance()->serialize($entity)
        );
    }
}
