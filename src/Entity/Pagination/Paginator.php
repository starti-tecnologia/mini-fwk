<?php

namespace Mini\Entity\Pagination;

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

    public function paginateQuery(array $options)
    {
        $this->processQueryHandlers($options);
        $query = $options['query'];
        $page = isset($options['page']) ? $options['page'] : 1;
        $perPage = isset($options['perPage']) ? $options['perPage'] : self::DEFAULT_PER_PAGE;

        return $this->paginateSql([
            'sql' => $query->makeSql(),
            'columnsQuantity' => count($query->spec['select']),
            'connectionInstance' => $query->connectionInstance,
            'bindings' => $query->spec['bindings'],
            'page' => $page,
            'perPage' => $perPage,
            'format' => $options['format']
        ]);
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
