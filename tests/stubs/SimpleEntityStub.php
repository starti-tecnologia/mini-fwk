<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class SimpleEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
    ];

    public $visible = [
        'id',
        'name',
    ];
}
