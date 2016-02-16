<?php

namespace Mini\Entity;

abstract class Entity
{

    /**
     * @var
     */
    protected $table;

    protected $useSoftDeletes = false;

    protected $useTimeStamps = false;

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }
}