<?php

namespace Mini\Entity;

use Mini\Exceptions\QueryException;

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
    public $connectionInstance = null;

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

    private function handleDefaultWhere($column, $comparator, $value, $operator='AND')
    {
        $rawValue = null;

        if ($value instanceof RawValue) {
            $rawValue = $value->value;
        } else {
            if (is_array($value) && ($comparator === 'IN' || $comparator === 'NOT IN')) {
                $params = [];
                foreach ($value as $item) {
                    $paramName = 'p' . ++$this->counter;
                    $this->spec['bindings'][$paramName] = $item;
                    $params[] = ':' . $paramName;
                }

                $rawValue = '(' . implode(', ', $params) . ')';
            } else {
                $paramName = 'p' . ++$this->counter;
                $this->spec['bindings'][$paramName] = $value;
                $rawValue = ':' . $paramName;
            }
        }

        return [$column, $comparator, $rawValue, $operator];
    }

    private function handleSubQueryWhere($query, $operator)
    {
        if ($operator === null) {
            $operator = 'AND';
        }

        $wheres = $query->spec['wheres'];
        $bindings = $query->spec['bindings'];
        $count = count($wheres);

        $mergedWheres = [];
        $mergedCounter = 0;
        $ignoredBindings = [];

        foreach ($wheres as $index => $where) {
            if ($where[2] === ':p' . $mergedCounter) {
                $oldParamName = 'p' . $mergedCounter;
                $newParamName = 'p' . ++$this->counter;
                $this->spec['bindings'][$newParamName] = $bindings[$oldParamName];
                $ignoredBindings[] = $oldParamName;

                $where[2] = ':' . $newParamName;
                ++$mergedCounter;
            }

            if ($index === 0) {
                $where[0] = '(' . $where[0];
                $where[3] = $operator;
            }

            if ($index === $count - 1) {
                $where[2] = $where[2] . ')';
            }

            $mergedWheres[] = $where;
        }

        $this->spec['bindings'] = array_merge(
            $this->spec['bindings'],
            array_except($bindings, $ignoredBindings)
        );

        return $mergedWheres;
    }

    public function where ($column, $comparator=null, $value=null, $operator='AND')
    {
        if (is_string($column)) {
            $this->spec['wheres'][] = $this->handleDefaultWhere($column, $comparator, $value, $operator);
        } elseif ($column instanceof Query) {
            $operator = $comparator;

            $this->spec['wheres'] = array_merge(
                $this->spec['wheres'],
                $this->handleSubQueryWhere($column, $operator)
            );
        }

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

    public function orderBy($column, $direction = 'ASC')
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
            $this->select([$instance->table . '.*']);
        }

        $addSelect = [];
        $mustIgnoreId = strstr($this->spec['select'][0], '*');

        foreach (array_keys($relationInstance->definition) as $key) {
            if ($mustIgnoreId && $key === $relationInstance->idAttribute) {
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

    /**
     * Get one row from database and perform object hydration
     *
     * @return Entity|null
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

    /**
     * Get one row from database and perform object hydration
     *
     * @throws QueryException
     *
     * @return Entity
     */
    public function getObjectOrFail()
    {
        $result = $this->getObject();

        if ($result === null) {
            $entityName = explode('/', $this->spec['class']);
            $entityName = end($entityName);

            throw new QueryException('No query results for entity ' . $entityName);
        }

        return $result;
    }

    /**
     * Get one row from database
     *
     * @return array|null
     */
    public function getArray()
    {
        if (! $this->spec['limit']) {
            $this->limit(0, 1);
        }
        $stm = $this->execute();
        $result = $stm->fetch(\PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    /**
     * Get one row from database
     *
     * @throws QueryException
     *
     * @return Entity
     */
    public function getArrayOrFail()
    {
        $result = $this->getArray();

        if ($result === null) {
            throw new QueryException('No query results for table ' . $this->spec['table']);
        }

        return $result;
    }

    /**
     * Get the value of the first column on results
     *
     * @return array
     */
    public function getColumn()
    {
        $stm = $this->execute();
        return $stm->fetchColumn();
    }

    /**
     * List rows using object serialization
     *
     * @return array|Entity
     */
    public function listObject()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_CLASS, $this->spec['class']);
    }

    /**
     * List rows from database
     *
     * @return array|Entity
     */
    public function listArray()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * List values from the first column on result
     *
     * @return array
     */
    public function listColumn()
    {
        $stm = $this->execute();
        return $stm->fetchAll(\PDO::FETCH_COLUMN);
    }
}
