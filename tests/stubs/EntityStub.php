<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class EntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'guid' => 'uuid|unique',
        'email' => 'email|unique',
        'name' => 'string:100',
        'password' => 'string:100:unusedparameter|unique',
        'customer_id' => 'integer|belongsTo:customers'
    ];
}
