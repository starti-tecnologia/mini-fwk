<?php

namespace Mini\Behaviors;

use Mini\Entity\Pagination\Paginator;
use Mini\Entity\Query;
use Mini\Helpers\Request;

/**
 * @deprecated Its better to implement pagination logic at application layer
 */
trait PaginatorAware
{

    public function basePaginate(Query $query, array $options=[]) {
        $paginator = new Paginator;
        $request = Request::instance();

        $page = max(intval($request->get('page')), 1);

        if ($request->get('perPage') !== null) {
            $perPage = max(intval($request->get('perPage')), 1);
        } else {
            $perPage = 20;
        }

        $output = $paginator->paginateQuery(
            array_merge(
                [
                    'query' => $query,
                    'filter' => $request->get('filter'),
                    'sort' => array_filter(explode(',', $request->get('sort'))),
                    'page' => $page,
                    'perPage' => $perPage
                ],
                $options
            )
        );

        return $output;
    }

    /**
     * Process and sends pagination result
     *
     * @return void
     */
    public function paginate(Query $query, array $options=[])
    {
        $output = $this->basePaginate($query, $options);

        response()->json($output);
    }

    /**
     * Process and sends pagination result
     *
     * @param array $options with given example:
     *
     * $options = [
     *     'sql' => $sql,
     *     'columnsQuantity' => 3,
     *     'bindings' => ['revendaID' => 2],
     *     'format' => [
     *       'column1', 'column2'
     *     ],
     *     'connection' => Admdns::getInstanceConnection()
     * ];
     *
     * $this->paginateSql($options);
     *
     * @return void
     */
    public function paginateSql(array $options)
    {
        $paginator = new Paginator;
        $request = Request::instance();

        $page = max(intval($request->get('page')), 1);

        if ($request->get('perPage') !== null) {
            $perPage = max(intval($request->get('perPage')), 1);
        } else {
            $perPage = 20;
        }

        $output = $paginator->paginateSql(
            [
                'sql' => $options['sql'],
                'columnsQuantity' => $options['columnsQuantity'],
                'connectionInstance' => $options['connection'],
                'bindings' => isset($options['bindings']) ? $options['bindings'] : null,
                'page' => $page,
                'perPage' => $perPage,
                'format' => $options['format']
            ]
        );

        response()->json($output);
    }
}
