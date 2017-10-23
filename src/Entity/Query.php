<?php

namespace Mini\Entity;

use Mini\Exceptions\QueryException;

class Query
{
    public $spec = [
        'alias' => 't',
        'class' => null,
        'table' => null,
        'rawTable' => null,
        'joins' => [],
        'wheres' => [],
        'having' => [],
        'orderBy' => [],
        'groupBy' => null,
        'select' => ['*'],
        'bindings' => [],
        'limit' => null,
        'relations' => []
    ];

    private $counter = -1;

    private $subQueryCounter = -1;

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
        if ($table instanceof Query) {
            $this->mergeQueryParameters($table);
            $this->spec['rawTable'] = $table->makeSql();
            $this->alias($table->spec['alias']);
        } else {
            $this->spec['table'] = $table;
            $this->alias($table);
        }

        return $this;
    }

    public function alias($alias)
    {
        $oldAlias = $this->spec['alias'];
        $this->spec['alias'] = $alias;

        foreach ($this->spec['wheres'] as &$where) {
            $where[0] = preg_replace(
                '/^' . preg_quote($oldAlias). '\./',
                $alias . '.',
                $where[0]
            );
        }

        return $this;
    }

    public function rawTable($table)
    {
        $this->spec['rawTable'] = $table;

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

    private function handleJoin($type, $table, $columnA, $comparator, $columnB)
    {
        if ($table instanceof Query) {
            $query = $table;
            $this->mergeQueryParameters($query);
            $table = '(' . $query->makeSql() . ') ' . quote_sql($query->spec['alias']);
        }

        $this->spec['joins'][] = [$type, $table, $columnA, $comparator, $columnB];
    }

    public function leftJoin($table, $columnA, $comparator, $columnB)
    {
        $this->handleJoin('LEFT JOIN', $table, $columnA, $comparator, $columnB);

        return $this;
    }

    public function innerJoin($table, $columnA, $comparator, $columnB)
    {
        $this->handleJoin('INNER JOIN', $table, $columnA, $comparator, $columnB);

        return $this;
    }

    public function rawJoin($sql)
    {
        $this->spec['joins'][] = new RawValue($sql);

        return $this;
    }

    public function renameBinding($oldKey, $newKey)
    {
        $newWheres = [];
        foreach ($this->spec['wheres'] as $where) {
            $newWheres[] = str_replace(':' . $oldKey, ':' . $newKey, $where);
        }
        $this->spec['wheres'] = $newWheres;
        $this->table = str_replace(':' . $oldKey, ':' . $newKey, $this->spec['table']);
    }

    public function mergeQueryParameters(Query $query)
    {
        ++$this->subQueryCounter;

        foreach ($query->spec['bindings'] as $oldKey => $value) {
            $newKey = 's' . $this->subQueryCounter . 'p' . ++$this->counter;
            $query->renameBinding($oldKey, $newKey);
            unset($query->spec['bindings'][$oldKey]);
            $query->spec['bindings'][$newKey] = $value;
        }

        $this->spec['bindings'] = array_merge(
            $this->spec['bindings'],
            $query->spec['bindings']
        );
    }

    private function handleDefaultComparation($column, $comparator, $value, $operator = 'AND')
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
            $isIn = false;
            if (strstr($where[2], '(:p' . $mergedCounter)) {
                $isIn = true;
            }
            if ($where[2] === ':p' . $mergedCounter || $isIn) {
                $oldWhere = $where[2];
                $oldWherePieces = explode(',', trim($oldWhere, '()'));
                $newWherePieces = [];
                foreach ($oldWherePieces as $oldWherePiece) {
                    $oldParamName = 'p' . $mergedCounter;
                    $newParamName = 'p' . ++$this->counter;
                    $this->spec['bindings'][$newParamName] = $bindings[$oldParamName];
                    $ignoredBindings[] = $oldParamName;

                    $newWherePieces[] = ':' . $newParamName;
                    ++$mergedCounter;
                }
                $where[2] = $isIn
                    ? '(' . implode(',', $newWherePieces) . ')'
                    : implode(',', $newWherePieces);
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
            $this->spec['wheres'][] = $this->handleDefaultComparation($column, $comparator, $value, $operator);
        } elseif ($column instanceof Query) {
            $operator = $comparator;

            $this->spec['wheres'] = array_merge(
                $this->spec['wheres'],
                $this->handleSubQueryWhere($column, $operator)
            );
        }

        return $this;
    }

    public function having ($column, $comparator=null, $value=null, $operator='AND')
    {
        $this->spec['having'][] = $this->handleDefaultComparation($column, $comparator, $value, $operator);
        return $this;
    }

    public function whereIsNull ($column, $operator='AND')
    {
        $this->spec['wheres'][] = [$column, 'IS', 'NULL', $operator];

        return $this;
    }

    public function whereIsNotNull ($column, $operator='AND')
    {
        $this->spec['wheres'][] = [$column, 'IS', 'NOT NULL', $operator];

        return $this;
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

    public function groupBy($column)
    {
        $this->spec['groupBy'] = is_array($column) ? $column : [$column];

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
    public function includeRelation($relation, $required = true, $fields = null)
    {
        $path = explode('.', $relation);
        $pathCount = count($path);
        $lastInstance = $this->getInstance();
        $lastAlias = $this->spec['alias'];
        for ($i = 0; $i < $pathCount - 1; $i++) {
            $step = $path[$i];
            $parentRelation = implode('.', array_slice ($path, 0, $i + 1));
            if ($i != $pathCount - 1 && ! isset($this->spec['relations'][$parentRelation])) {
                throw new \Exception('Parent relation needed: ' . $step);
            }
            $lastInstance = new $lastInstance->relations[$step]['class'];
            $lastAlias = $step;
        }
        $this->processIncludeRelation(
            $lastInstance,
            $lastAlias,
            $path[$pathCount - 1],
            $required ,
            $fields
        );
        $this->spec['relations'][$relation] = ['required' => true, 'fields' => $fields];
        return $this;
    }

    private function processIncludeRelation(
        Entity $instance,
        $alias,
        $relation,
        $required = true,
        $fields = null
    ) {
        $method = $required ? 'innerJoin' : 'leftJoin';
        $relationArray = $instance->relations[$relation];
        $relationInstance = new $relationArray['class'];
        $isReversed = false;
        $includeCurrentAliasAsPrefix = $instance != $this->getInstance();
        $selectPrefix = $includeCurrentAliasAsPrefix
            ? $alias . '_' . $relation
            : $relation;

        if (isset($relationArray['field'])) {
            $relationField = $relationArray['field'];
            $this->$method(
                $relationInstance->table . ' ' . $selectPrefix,
                $alias . '.' . $relationField,
                '=',
                $selectPrefix . '.' . $relationInstance->idAttribute
            );
        } elseif (isset($relationArray['reference'])) {
            $isReversed = true;
            $relationField = $relationInstance->relations[$relationArray['reference']]['field'];
            $this->$method(
                $relationInstance->table . ' ' . $selectPrefix,
                $selectPrefix . '.' . $relationField,
                '=',
                $alias . '.' . $instance->idAttribute
            );
        } else {
            throw new \Exception('Unknow relation type');
        }

        if ($this->spec['select'] === ['*']) {
            $this->select([$this->spec['alias'] . '.*']);
        }

        $addSelect = [];
        $mustIgnoreId = ! $isReversed && (
            strstr($this->spec['select'][0], '*') && isset($instance->definition[
                $relation . '_' . $relationInstance->idAttribute
            ])
        );
        $relationKeys = array_keys($relationInstance->definition);
        if ($relationInstance->useTimeStamps && ! in_array($relationInstance->updatedAttribute, $relationKeys)) {
            $relationKeys[] = $relationInstance->updatedAttribute;
            $relationKeys[] = $relationInstance->createdAttribute;
        }
        foreach ($relationKeys as $key) {
            if ($mustIgnoreId && $key === $relationInstance->idAttribute) {
                continue;
            }

            if ($fields !== null && in_array($key, $fields) == false) {
                continue;
            }

            $addSelect[] = $selectPrefix . '.' . $key . ' as ' . $selectPrefix . '_' . $key;
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

            $sql .= sprintf(
                '%s %s %s', quote_sql($where[0]), $where[1], $where[2]
            );
        }

        return $sql;
    }

    /**
     * Sql building functions
     */
    public function makeHavingSql()
    {
        $sql = '';

        $count = count($this->spec['having']);

        foreach ($this->spec['having'] as $i => $having) {
            if ($i > 0) {
                $sql .= ' ' . $having[3] . ' ';
            }

            $sql .= sprintf(
                '%s %s %s', quote_sql($having[0]), $having[1], $having[2]
            );
        }

        return $sql;
    }

    public function makeSelectSql()
    {
        return implode(
            ', ',
            array_map(
                'quote_sql',
                $this->spec['select']
            )
        );
    }

    public function makeJoinSql()
    {
        return implode(' ', array_map(function ($join) {
            if ($join instanceof RawValue) {
                return $join->value;
            } else {
                if (! strstr($join[1], 'SELECT')) {
                    $join[1] = quote_sql($join[1]);
                }
                $join[2] = quote_sql($join[2]);
                $join[4] = quote_sql($join[4]);
                return vsprintf('%s %s ON (%s %s %s)', $join);
            }
        }, $this->spec['joins']));
    }

    public function makeOrderBySql()
    {
        return implode(', ', array_map(function ($orderBy) {
            $orderBy[0] = quote_sql($orderBy[0]);
            return vsprintf('%s %s', $orderBy);
        }, $this->spec['orderBy']));
    }

    public function makeGroupBySql()
    {
        return implode(', ', array_map(function ($groupBy) {
            return quote_sql($groupBy);
        }, $this->spec['groupBy']));
    }

    public function makeFromSql()
    {
        $sql = null;

        if ($this->spec['rawTable']) {
            $sql = '(' . $this->spec['rawTable'] . ') ' . quote_sql($this->spec['alias']);
        } else {
            $sql = quote_sql($this->spec['table']) . (
                $this->spec['table'] == $this->spec['alias']
                ? ''
                : ' ' . quote_sql($this->spec['alias'])
            );
        }

        return $sql;
    }

    public function makeSql()
    {
        $sql = 'SELECT ' . $this->makeSelectSql() .  ' FROM ';

        $sql .= $this->makeFromSql();

        if (count($this->spec['joins'])) {
            $sql .= ' ' . $this->makeJoinSql();
        }

        if (count($this->spec['wheres'])) {
            $sql .= ' WHERE ' . $this->makeWhereSql();
        }

        if (count($this->spec['groupBy'])) {
            $sql .= ' GROUP BY ' . $this->makeGroupBySql();
        }

        if (count($this->spec['having'])) {
            $sql .= ' HAVING ' . $this->makeHavingSql();
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
        $row = $stm->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $object = new $this->spec['class'];
            $object->setStoredFields($row);
            return $object;
        }
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
        $objects = [];
        foreach ($stm->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $object = new $this->spec['class'];
            $object->setStoredFields($row);
            $objects[] = $object;
        };
        return $objects;
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
