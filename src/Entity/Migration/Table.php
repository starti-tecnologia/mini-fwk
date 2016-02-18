<?php

namespace Mini\Entity\Migration;

class Table
{
    /**
     * @var string Name of the table on database
     */
    public $name;

    /**
     * @var TableItem Item that represents a column or a constraint
     */
    public $items;

    public function _constraint()
    {
        $this->items = [];
    }

    public function getCreateSql()
    {
        $createTable = 'CREATE TABLE %s (%s); %s';

        $itemsByType = [
            TableItem::TYPE_COLUMN => [],
            TableItem::TYPE_CONSTRAINT => []
        ];

        foreach ($this->items as $column) {
            $itemsByType[$column->type][] = $column;
        }

        $columnSql = implode(',', array_map(function ($item) {
            return $item->sql;
        }, $itemsByType[TableItem::TYPE_COLUMN]));

        $constraintSql = implode('', array_map(function ($item) {
            return $item->sql . '; ';
        }, $itemsByType[TableItem::TYPE_CONSTRAINT]));

        return trim(sprintf($createTable, $this->name, $columnSql, $constraintSql));
    }
}
