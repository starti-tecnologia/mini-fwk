<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class ReversedRelationEntityStub extends Entity
{
    use QueryAware;

    public $table = 'reverseds';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'relation_id' => 'integer',
    ];

    public $visible = [
        'id',
        'name',
    ];

    public $relations = [
        'relation' => [
            'class' => RelationEntityStub::class,
            'field' => 'relation_id'
        ]
    ];
}
