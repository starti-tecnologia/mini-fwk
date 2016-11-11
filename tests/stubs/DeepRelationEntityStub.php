<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class DeepRelationEntityStub extends Entity
{
    use QueryAware;

    public $table = 'deep';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'more_deep_id' => 'integer'
    ];

    public $visible = [
        'id',
        'name',
    ];

    public $relations = [
        'moreDeep' => [
            'class' => 'DeepRelationEntityStub',
            'field' => 'more_deep_id'
        ]
    ];
}
