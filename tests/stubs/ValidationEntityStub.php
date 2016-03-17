<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class ValidationEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'name' => 'string|required',
    ];
}
