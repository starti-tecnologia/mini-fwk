<?php

use Mini\Entity\Entity;

class EntityTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/FakeConnectionManager.php';
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/PrefixObjectEntityStub.php';

        $this->connectionManager = new FakeConnectionManager;

        app()->register('Mini\Entity\ConnectionManager', function () {
            return $this->connectionManager;
        });
    }

    public function testIsSettingRelation()
    {
        $owner = new SimpleEntityStub;
        $owner->id = 2;

        $entity = new RelationEntityStub;
        $entity->setRelation('owner', $owner);

        $this->assertEquals(2, $entity->getRelation('owner')->id);
        $this->assertEquals(2, $entity->owner_id);
    }

    public function testIsGettingRelationProxy()
    {
        $entity = new RelationEntityStub;
        $entity->owner_id = 1;
        $entity->owner_name = 'Lala';

        $proxy = $entity->getRelation('owner');
        $this->assertEquals(1, $proxy->id);
        $this->assertEquals('Lala', $proxy->name);
    }

    private function getFilledPrefixObjectEntity()
    {
        $entity = new PrefixObjectEntityStub;

        $entity->fill([
            'name' => 'Lala',
            'max_users_quantity' => 10,
            'address' => [
                'street_name' => 'Some street',
                'number' => '1C'
            ]
        ]);

        return $entity;
    }

    public function testIsFillingPrefixedObjects()
    {
        $entity = $this->getFilledPrefixObjectEntity();
        $this->assertEquals('Lala', $entity->name);
        $this->assertEquals('Some street', $entity->address_street_name);
        $this->assertEquals('1C', $entity->address_number);
    }

    public function testIsSerializingPrefixedObjects()
    {
        $entity = $this->getFilledPrefixObjectEntity();
        $this->assertEquals(
            [
                'name' => 'Lala',
                'max_users_quantity' => 10,
                'address' => [
                    'street_name' => 'Some street',
                    'number' => '1C'
                ]
            ],
            $entity->jsonSerialize()
        );
    }

    public function testIsFillingEverything()
    {
        $simple = new SimpleEntityStub;
        $simple->fill([
            'id' => 'lala',
            'name' => 'lala'
        ]);
        $this->assertEquals('lala', $simple->id);
        $this->assertEquals('lala', $simple->name);
    }
}
