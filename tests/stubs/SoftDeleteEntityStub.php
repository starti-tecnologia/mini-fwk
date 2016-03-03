<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class SoftDeleteEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $useSoftDeletes = true;

    public $definition = [
        'id' => 'pk',
        'name' => 'string:100'
    ];
}
