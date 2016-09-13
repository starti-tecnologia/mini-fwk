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
        'owner_will_go' => 'boolean',
        'deep_id' => 'integer'
    ];

    public $visible = [
        'id',
        'name',
        'owner_id',
        'owner_will_go',
    ];

    public $fillable = [
        'name',
        'owner',
    ];

    public $relations = [
        'owner' => [
            'class' => SimpleEntityStub::class,
            'field' => 'owner_id'
        ],
        'reversed' => [
            'class' => ReversedRelationEntityStub::class,
            'reference' => 'relation'
        ],
        'deep' => [
            'class' => DeepRelationEntityStub::class,
            'field' => 'deep_id'
        ]
    ];
}
