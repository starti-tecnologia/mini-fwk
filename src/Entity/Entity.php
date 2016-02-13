<?php

namespace Mini\Entity;

abstract class Entity
{

    /**
     * @var
     */
    protected $table;

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }
}