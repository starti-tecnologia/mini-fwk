<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class EmptyDefaultEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'field' => 'string:20|default:'
    ];
}
