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
}
