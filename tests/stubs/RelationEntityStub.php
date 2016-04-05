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
        'owner_id' => 'integer|belongsTo:customers'
    ];

    public $visible = [
        'id',
        'name',
        'owner_id',
    ];

    public $relations = [
        'owner' => [
            'class' => SimpleEntityStub::class,
            'field' => 'owner_id'
        ]
    ];
}
