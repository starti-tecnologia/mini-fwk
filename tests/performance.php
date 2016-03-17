#!/usr/bin/env php
<?php

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../src/Helpers/Instance/helpers.php';
require __DIR__ . '/stubs/SerializableEntityStub.php';
require __DIR__ . '/stubs/SimpleEntityStub.php';

use Mini\Entity\Entity;
use Mini\Entity\EntitySerializer;

function testPerformance($name, $prepareFn, $runFn) {
    $context = $prepareFn();

    echo ':: Executing performance test: ' . $name . PHP_EOL;

    $start = microtime(true);
    $runFn($context);
    $end = microtime(true);

    echo 'Done. Test finished in ' . ($end - $start) . ' seconds.'. PHP_EOL . PHP_EOL;
}

testPerformance(
    'Entity Serialization',
    function () {
        $entity = new SerializableEntityStub;

        $entity->fields = [
            'id' => '1',
            'name' => 'Lala',
            'is_draft' => '1',
            'max_users_quantity' => '10',
            'address_street_name' => 'Lala Street',
            'address_number' => '1C',
            'owner_id' => '1',
            'owner_name' => 'John'
        ];

        return $entity;
    },
    function ($entity) {
        $loops = 50000;

        echo "Doing $loops loops over entity serialization\n";

        for ($i = 0; $i < 50000; $i++) {
            EntitySerializer::instance()->serialize($entity);
        }
    }
);
