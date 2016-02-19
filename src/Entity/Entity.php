<?php

namespace Mini\Entity;

abstract class Entity
{
    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $connection = 'default';

    /**
     * @var boolean
     */
    public $useSoftDeletes = false;

    /**
     * @var boolean
     */
    public $useTimeStamps = false;
}
