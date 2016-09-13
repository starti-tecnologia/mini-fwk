<?php

use Mini\Entity\Entity;
use Mini\Helpers\Fake\FakeConnectionManager;

class EntityTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/PrefixObjectEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/UnderscoreEntityStub.php';

        $this->connectionManager = new FakeConnectionManager;

        app()->register('Mini\Entity\ConnectionManager', function () {
            return $this->connectionManager;
        });
    }

    public function assertSqlPattern($pattern)
    {
        $count = 0;
        foreach ($this->connectionManager->log as $log) {
            if (preg_match($pattern, $log[1])) {
                $count = 1;
            }
        }
        $this->assertEquals(1, $count, 'Failed asserting sql pattern: ' . $pattern);
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

    public function testIsFillingRelationByGuid()
    {
        $this->connectionManager->fixtures['/SELECT/'] = [
            [
                'id' => 16346,
                'name' => 'Owner name'
            ]
        ];

        $entity = new RelationEntityStub;
        $entity->fill([
            'name' => 'lala',
            'owner' => 'SOME_GUID'
        ]);
        $this->assertEquals('lala', $entity->name);
        $this->assertEquals(16346, $entity->getRelation('owner')->id);
        $this->assertEquals('Owner name', $entity->getRelation('owner')->name);
        $this->assertSqlPattern('/WHERE `guid` = /');
    }

    public function testIsFillingRelationById()
    {
        $this->connectionManager->fixtures['/SELECT/'] = [
            [
                'id' => 16346,
                'name' => 'Owner name'
            ]
        ];

        $entity = new RelationEntityStub;
        $entity->fill([
            'name' => 'lala',
            'owner' => 16346
        ]);
        $this->assertEquals('lala', $entity->name);
        $this->assertEquals(16346, $entity->getRelation('owner')->id);
        $this->assertSqlPattern('/WHERE `id` = /');
    }

    public function testIsFillingCamelCaseArraysWhenConvertCamelCaseIsEnabled()
    {
        putenv('CONVERT_CAMEL_CASE=1');
        $entity = new UnderscoreEntityStub;
        $entity->fill([
            'name' => 'Name',
            'governmentEstablishmentId' => '1',
            'businessName' => 'bname',
            'isAnvisa' => true,
            'color' => '#012313',
            'addressStreetName' => 'A Street',
            'addressLatitude' => '1.659',
        ]);
        $this->assertEquals('Name', $entity->name);
        $this->assertEquals('1', $entity->government_establishment_id);
        $this->assertEquals('bname', $entity->business_name);
        $this->assertEquals(1, $entity->is_anvisa);
        $this->assertEquals('#012313', $entity->color);
        $this->assertEquals('A Street', $entity->address_street_name);
        $this->assertEquals('1.659', $entity->address_latitude);
        putenv('CONVERT_CAMEL_CASE=0');
    }

    public function testIsNotFillingCamelCaseArraysWhenConvertCamelCaseIsDisabled()
    {
        $entity = new UnderscoreEntityStub;
        $entity->fill([
            'name' => 'Name',
            'government_establishment_id' => '1',
            'business_name' => 'bname',
            'is_anvisa' => true,
            'color' => '#012313',
            'address_street_name' => 'A Street',
            'address_latitude' => '1.659',
        ]);
        $this->assertEquals('Name', $entity->name);
        $this->assertEquals('1', $entity->government_establishment_id);
        $this->assertEquals('bname', $entity->business_name);
        $this->assertEquals(1, $entity->is_anvisa);
        $this->assertEquals('#012313', $entity->color);
        $this->assertEquals('A Street', $entity->address_street_name);
        $this->assertEquals('1.659', $entity->address_latitude);
    }
}
