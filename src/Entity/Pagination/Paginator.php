<?php

namespace Mini\Entity\Pagination;

use Mini\Entity\Entity;
use Mini\Entity\Query;

class Paginator
{
    const DEFAULT_PER_PAGE = 10;

    private $outputSerializer = null;

    public function __construct()
    {
        $this->outputSerializer = new OutputSerializer;
    }

    private function filterInputColumn ($value) {
        return '`'. preg_replace('/[^A-Za-z0-9_]/', '', $value) . '`';
    }

    public function processQueryHandlers(array $options)
    {
        $query = $options['query'];
        $filter = empty($options['filter']) ? [] : $options['filter'];
        $sort = empty($options['sort']) ? [] : $options['sort'];
        $filterHandlers = isset($options['filterHandlers']) ? $options['filterHandlers'] : null;
        $sortHandlers = isset($options['sortHandlers']) ? $options['sortHandlers'] : null;

        foreach ($filter as $column => $value) {
            if (isset($filterHandlers[$column])) {
                $filterHandlers[$column]($query, $value);
            } else {
                $query->where($this->filterInputColumn($column), '=', $value);
            }
        }

        foreach ($sort as $column) {
           $direction = 'ASC';

            if (strpos($column, '-') === 0) {
                $direction = 'DESC';
                $column = substr($column, 1);
            }

            if (isset($sortHandlers[$column])) {
                $sortHandlers[$column]($query, $direction);
            } else {
                $query->orderBy($this->filterInputColumn($column), $direction);
            }
        }
    }

    private function processFormatDefinition(Entity $instance, $field)
    {
        $definition = isset($instance->definition[$field]) ? $instance->definition[$field] : null;

        if ($definition === null) {
            return $field;
        }

        if (strpos($definition, 'integer') !== false || strpos($definition, 'pk') !== false) {
            $field .= '|integer';
        } elseif (strpos($definition, 'float') !== false ||
            strpos($definition, 'double') !== false ||
            strpos($definition, 'decimal') !== false) {
            $field .= '|float';
        } elseif (strpos($definition, 'boolean') !== false) {
            $field .= '|boolean';
        } elseif (strpos($definition, 'datetime') !== false || strpos($definition, 'timestamp') !== false) {
            $field .= '|datetime';
        } elseif (strpos($definition, 'date') !== false) {
            $field .= '|date';
        }

        return $field;
    }

    private function generateDefaultFormat(array $options)
    {
        $query = $options['query'];
        $instance = $query->getInstance();
        $relations = $instance->relations;

        $format = [];

        foreach ($query->spec['select'] as $rawField) {
            $hasPoint = strpos($rawField, '.') !== false;
            $hasSpace = strpos($rawField, ' ') !== false;

            if ($hasPoint && $hasSpace) {
                $field = preg_split('/[. ]/', $rawField);
                $field = end($field);
            } else {
                $field = explode($hasPoint ? '.' : ' ', $rawField);
                $field = end($field);
            }

            $isRelation = false;

            foreach ($relations as $relationName => $relationOptions) {
                $prefix = $relationName . '_';

                if (strpos($field, $prefix) !== 0) {
                    continue;
                }

                $isRelation = true;
                $fieldWithoutPrefix = substr($field, strlen($prefix));
                $formatKey = $relationName . '|object|prefix:' . $relationName . '_';

                if (! isset($format[$formatKey])) {
                    $format[$formatKey] = [];
                }

                if (! isset($relations[$relationName]['instance'])) {
                    $relations[$relationName]['instance'] = new $relations[$relationName]['class'];
                }

                $relationInstance = $relations[$relationName]['instance'];
                $visible = $relationInstance->visible;

                if (isset($visible[0]) && ! in_array($fieldWithoutPrefix, $visible)) {
                    continue;
                }

                $format[$formatKey][] = $this->processFormatDefinition(
                    $relationInstance,
                    $fieldWithoutPrefix
                );
            }

            if (! $isRelation) {
                $format[] = $this->processFormatDefinition(
                    $instance,
                    $field
                );
            }
        }

        return $format;
    }

    public function processQueryOptions(array $options)
    {
        $this->processQueryHandlers($options);
        $query = $options['query'];
        $page = isset($options['page']) ? $options['page'] : 1;
        $perPage = isset($options['perPage']) ? $options['perPage'] : self::DEFAULT_PER_PAGE;
        $format = isset($options['format']) ? $options['format'] : $this->generateDefaultFormat($options);

        return [
            'sql' => $query->makeSql(),
            'columnsQuantity' => count($query->spec['select']),
            'connectionInstance' => $query->connectionInstance,
            'bindings' => $query->spec['bindings'],
            'page' => $page,
            'perPage' => $perPage,
            'format' => $format
        ];
    }

    public function paginateQuery(array $options)
    {
        return $this->paginateSql(
            $this->processQueryOptions($options)
        );
    }

    public function paginateSql(array $options)
    {
        $result = $this->runPaginatorSelect($options);
        return $this->makeOutput($result, $options);
    }

    public function makeOutput(array $result, array $options)
    {
        return $this->outputSerializer->serialize($result, $options);
    }

    private function runPaginatorSelect(array $options)
    {
        $bindings = $options['bindings'];
        $sql = $this->createPaginatorSelect($options);
        $stm = $options['connectionInstance']->prepare($sql);
        $stm->execute($bindings);
        $rows = $stm->fetchAll(\PDO::FETCH_ASSOC);
        $lastRow = array_pop($rows);

        return [
            'rows' => $rows,
            'total' => intval($lastRow['__pagination_total'])
        ];
    }

    private function createPaginatorSelect(array $options)
    {
        $initialSql = $options['sql'];
        $columnsQuantity = $options['columnsQuantity'];
        $page = $options['page'];
        $perPage = $options['perPage'];

        $union = str_repeat('0,', $columnsQuantity) . 'found_rows()';
        $skip = ($page - 1) * $perPage;

        $sql = preg_replace(
            '/^SELECT (.*?)FROM(.*)$/i',
            'SELECT SQL_CALC_FOUND_ROWS t0.* FROM (SELECT $1, 0 as __pagination_total FROM $2) t0 ' .
            ' LIMIT ' . $skip . ', ' . $perPage .
            ' UNION ALL SELECT ' . $union,
            $initialSql
        );

        return $sql;
    }
}
