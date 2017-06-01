<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class MultipleColumnPrimaryKeyStub extends Entity
{
    use QueryAware;

    public $table = 'users_groups';

    public $idAttribute = ['user_id', 'group_id'];

    public $definition = [
        'user_id' => 'integer',
        'group_id' => 'integer',
        'active' => 'integer'
    ];
}
