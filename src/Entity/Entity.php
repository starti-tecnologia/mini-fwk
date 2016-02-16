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
     * @return boolean
     */
    public function isUseSoftDeletes()
    {
        return $this->useSoftDeletes;
    }

    /**
     * @return boolean
     */
    public function isUseTimeStamps()
    {
        return $this->useTimeStamps;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }
}