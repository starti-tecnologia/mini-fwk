<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class EntityIndexStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'title' => 'string',
    ];

    public $fillable = [
        '*'
    ];

    public $indexes = [
        'name' => 'name',
        'name_title' => 'name,title|unique'
    ];
}
