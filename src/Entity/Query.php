<?php

namespace Mini\Entity;

class Query
{
    public $spec = [
        'alias' => 't',
        'class' => null,
        'table' => null,
        'joins' => [],
        'wheres' => [],
        'orderBy' => [],
        'select' => ['*'],
        'bindings' => [],
        'limit' => null
    ];

    private $counter = -1;

    /**
     * @var \Mini\Entity\Connection
     */
    private $connectionInstance = null;

    /**
     * @var \Mini\Entity\Entity
     */
    private $instance = null;

    /**
     * Preparation functions
     */
    public function table($table)
    {
        $this->spec['table'] = $table;

        return $this;
    }

    public function connection($connection)
    {
        if (is_string($connection)) {
            $connection = app()->get('Mini\Entity\ConnectionManager')->getConnection($connection);
        }

        $this->connectionInstance = $connection;

        return $this;
    }

    public function className($className)
    {
        $this->spec['class'] = $className;

        return $this;
    }

    public function leftJoin($table, $columnA, $comparator, $columnB)
    {
        $this->spec['joins'][] = ['LEFT JOIN', $table, $columnA, $comparator, $columnB];

        return $this;
    }

    public function innerJoin($table, $columnA, $comparator, $columnB)
    {
        $this->spec['joins'][] = ['INNER JOIN', $table, $columnA, $comparator, $columnB];

        return $this;
    }

    public function where ($column, $comparator, $value, $operator='AND')
    {
        $rawValue = null;

        if ($value instanceof RawValue) {
            $rawValue = $value->value;
        } else {
            $paramName = 'p' . ++$this->counter;
            $this->spec['bindings'][$paramName] = $value;
            $rawValue = ':' . $paramName;
        }

        $this->spec['wheres'][] = [$column, $comparator, $rawValue, $operator];

        return $this;
    }

    public function whereIsNull ($column, $operator='AND')
    {
        $this->spec['wheres'][] = [$column, 'IS', 'NULL', $operator];
    }

    public function whereIsNotNull ($column, $operator='AND')
    {
        $this->spec['wheres'][] = [$column, 'IS', 'NULL', $operator];
    }

    public function select($columns)
    {
        $this->spec['select'] = $columns;

        return $this;
    }

    public function addSelect($columns)
    {
        $this->spec['select'] = array_merge($this->spec['select'], $columns);

        return $this;
    }

    public function orderBy($column, $direction='ASC')
    {
        $this->spec['orderBy'][] = [$column, $direction];

        return $this;
    }

    public function limit($offset, $rowCount)
    {
        $this->spec['limit'] = [$offset, $rowCount];

        return $this;
    }

    public function setParameter($key, $value)
    {
        $this->spec['bindings'][$key] = $value;

        return $this;
    }

    /**
     * Entity aware functions
     */
    public function getInstance()
    {
        if (! $this->instance) {
            $this->instance = new $this->spec['class'];
        }

        return $this->instance;
    }

    /**
     * Perform the left join and the select of a related entity
     *
     * @return self
     */
    public function includeRelation($relation, $required = true)
    {
        $method = $required ? 'innerJoin' : 'leftJoin';
        $instance = $this->getInstance();
        $relationArray = $instance->relations[$relation];
        $relationInstance = new $relationArray['class'];
        $relationField = $relationArray['field'];

        $this->$method(
            $relationInstance->table . ' ' . $relation,
            $instance->table . '.' . $relationField,
            '=',
            $relation . '.' . $relationInstance->idAttribute
        );

        if ($this->spec['select'] === ['*']) {
            $select = [];
            foreach (array_keys($instance->definition) as $key) {
                $select[] = $instance->table . '.' . $key;
            }
            $this->select($select);
        }

        $addSelect = [];

        foreach (array_keys($relationInstance->definition) as $key) {
            if ($key === $relationInstance->idAttribute) {
                continue;
            }

            $addSelect[] = $relation . '.' . $key . ' as ' . $relation . '_' . $key;
        }

        $this->addSelect($addSelect);

        return $this;
    }

    /**
     * Sql building functions
     */
    public function makeWhereSql()
    {
        $sql = '';

        $count = count($this->spec['wheres']);

        foreach ($this->spec['wheres'] as $i => $where) {
            if ($i > 0) {
                $sql .= ' ' . $where[3] . ' ';
            }

            $sql .= sprintf('%s %s %s', $where[0], $where[1], $where[2]);
        }

        return $sql;
    }

    public function makeSelectSql()
    {
        return implode(', ', $this->spec['select']);
    }

    public function makeJoinSql()
    {
        return implode(' ', array_map(function ($join) {
            return vsprintf('%s %s ON (%s %s %s)', $join);
        }, $this->spec['joins']));
    }

    public function makeOrderBySql()
    {
        return implode(', ', array_map(function ($orderBy) {
            return vsprintf('%s %s', $orderBy);
        }, $this->spec['orderBy']));
    }

    public function makeSql()
    {
        $sql = 'SELECT ' . $this->makeSelectSql() .  ' FROM ' . $this->spec['table'];

        if (count($this->spec['joins'])) {
            $sql .= ' ' . $this->makeJoinSql();
        }

        if (count($this->spec['wheres'])) {
            $sql .= ' WHERE ' . $this->makeWhereSql();
        }

        if (count($this->spec['orderBy'])) {
            $sql .= ' ORDER BY ' . $this->makeOrderBySql();
        }

        if (count($this->spec['limit'])) {
            $sql .= ' LIMIT ' . $this->spec['limit'][0] . ', ' . $this->spec['limit'][1];
        }

        return $sql;
    }

    private function execute()
    {
        $stm = $this->connectionInstance->prepare($this->makeSql());
        $stm->execute($this->spec['bindings']);

        return $stm;
    }

    /**
     * Database fetch functions
     */
    public function getObject()
    {
        if (! $this->spec['limit']) {
            $this->limit(0, 1);
        }
        $stm = $this->execute();
        $result = $stm->fetchObject($this->spec['class']);
        return $result ? $result : null;
    }

    public function getArray()
    {
        if (! $this->spec['limit']) {
            $this->limit(0, 1);
        }
        $stm = $this->execute();
        $result = $stm->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    public function getColumn()
    {
        $stm = $this->execute();
        return $stm->fetchColumn();
    }

    public function listObject()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_CLASS, $this->spec['class']);
    }

    public function listArray()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listColumn()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_COLUMN);
    }
}
