<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class FieldOrderEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'field0' => 'integer|belongsTo:table',
        'field1' => 'string',
        'field2' => 'integer|belongsTo:table',
        'field3' => 'string',
        'field4' => 'integer|belongsTo:table',
        'field5' => 'string',
        'field6' => 'integer|belongsTo:table',
        'field7' => 'string',
        'field8' => 'integer|belongsTo:table',
        'field9' => 'string'
    ];
}
