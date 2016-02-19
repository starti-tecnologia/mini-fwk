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

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->items = [];
    }

    public function makeCreateSql()
    {
        $createTable = 'CREATE TABLE %s ( %s ) COMMENT \'MINI_FWK_ENTITY\';%s';

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
            return $item->sql . ';';
        }, $itemsByType[TableItem::TYPE_CONSTRAINT]));

        return trim(sprintf($createTable, $this->name, trim($columnSql), trim($constraintSql)), ' ;');
    }

    public function makeDropSql()
    {
        return sprintf('DROP TABLE %s', $this->name);
    }

    public function makeAddOperations(Table $other)
    {
        $newItems = array_diff(array_keys($this->items), array_keys($other->items));

        $operations = [];
        foreach ($newItems as $itemKey) {
            $operations[] = $this->makeAddOperation($this->items[$itemKey]);
        }

        return $operations;
    }

    public function makeAddOperation(TableItem $item)
    {
        if ($item->type == TableItem::TYPE_COLUMN) {
            $sql = sprintf('ALTER TABLE %s ADD COLUMN %s', $this->name, $item->sql);
        } else if ($item->type == TableItem::TYPE_CONSTRAINT) {
            $sql = $item->sql;
        }

        return $sql;
    }

    public function makeModifyOperations(Table $other)
    {
        $operations = [];

        foreach ($this->items as $name => $item) {
            $otherItem = isset($other->items[$name]) ? $other->items[$name] : null;

            if (! $otherItem) {
                continue;
            }

            if ($item->sql != $otherItem->sql) {
                $operations[] = $this->makeModifyOperation($item);
            }
        }

        return $operations;
    }

    public function makeModifyOperation(TableItem $item)
    {
        if ($item->type == TableItem::TYPE_COLUMN) {
            $sql = sprintf('ALTER TABLE %s MODIFY COLUMN %s', $this->name, $item->sql);
        } else if ($item->type == TableItem::TYPE_CONSTRAINT) {
            $sql = '';

            if (strstr($item->sql, 'INDEX')) {
                $sql .= 'DROP INDEX ' . $item->name;
            } elseif (strstr($item->sql, 'FOREIGN KEY')) {
                $sql .= 'DROP FOREIGN KEY ' . $item->name;
            }

            $sql .= ';' . $item->sql;
        }

        return $sql;
    }

    public function validateModifyOperation($operation)
    {
        if (strstr($operation, ' not null') && ! strstr($operation, ' default ')) {
            throw new \Exception(
                'You need to set a default value when changing a column from nullable to not null: ' .
                $operation
            );
        }
    }


    public function makeDropOperations(Table $other)
    {
        $dropItems = array_diff(array_keys($other->items), array_keys($this->items));

        $operations = [];
        foreach ($dropItems as $itemKey) {
            $operations[] = $this->makeDropOperation($other->items[$itemKey]);
        }

        return $operations;
    }

    public function makeDropOperation(TableItem $item)
    {
        if ($item->type == TableItem::TYPE_COLUMN) {
            $sql = sprintf('ALTER TABLE %s DROP COLUMN %s', $this->name, $item->name);
        } else if ($item->type == TableItem::TYPE_CONSTRAINT) {
            if (strstr($item->sql, 'INDEX')) {
                $sql = 'DROP INDEX ' . $item->name;
            } elseif (strstr($item->sql, 'FOREIGN KEY')) {
                $sql = 'DROP FOREIGN KEY ' . $item->name;
            }
        }

        return $sql;
    }
}
