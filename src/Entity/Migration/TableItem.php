<?php

namespace Mini\Entity\Migration;

class TableItem
{
    const TYPE_COLUMN = 1;
    const TYPE_CONSTRAINT = 2;

    /**
     * @var string Item type
     */
    public $type;

    /**
     * @var string Name of the column or constraint
     */
    public $name;

    /**
     * @var string Sql
     */
    public $sql;

    public function __construct($type, $name, $sql = '')
    {
        $this->type = $type;
        $this->name = $name;
        $this->sql = $sql;
    }
}
