<?php

namespace Mini\Entity;

use Mini\Entity\Behaviors\QueryAware;

abstract class Entity
{
    use QueryAware;
}