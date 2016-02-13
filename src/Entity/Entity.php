<?php

namespace Mini\Entity;

use Mini\Entity\Behaviors\QueryAware;

class Entity
{
    use QueryAware;

    public $table = '';

}