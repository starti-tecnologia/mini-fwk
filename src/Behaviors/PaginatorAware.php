<?php

namespace Mini\Behaviors;

use Mini\Entity\Pagination\Paginator;
use Mini\Entity\Query;
use Mini\Helpers\Request;

trait PaginatorAware
{
    /**
     * Process and sends pagination result
     *
     * @return void
     */
    public function paginate(Query $query, array $options=[])
    {
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

        response()->json($output);
    }
}
