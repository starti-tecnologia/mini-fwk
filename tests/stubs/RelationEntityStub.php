<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class RelationEntityStub extends Entity
{
    use QueryAware;

    public $table = 'posts';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'owner_id' => 'integer|belongsTo:customers',
        'owner_will_go' => 'boolean'
    ];

    public $visible = [
        'id',
        'name',
        'owner_id',
        'owner_will_go',
    ];

    public $relations = [
        'owner' => [
            'class' => SimpleEntityStub::class,
            'field' => 'owner_id'
        ]
    ];
}
