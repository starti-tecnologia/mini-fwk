<?php

namespace Mini\Entity;

abstract class Entity
{

    /**
     * @var
     */
    public $table;

    public $useSoftDeletes = false;

    public $useTimeStamps = false;

}