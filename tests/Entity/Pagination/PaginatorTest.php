<?php

use Mini\Entity\Query;
use Mini\Entity\Pagination\Paginator;

class PaginatorTest extends PHPUnit_Framework_TestCase
{
    public function testIsProcessDefaultQueryHandlers()
    {
        $paginator = new Paginator;
        $query = (new Query)->table('users');

        $paginator->processQueryHandlers([
            'query' => $query,
            'filter' => [
                'name' => 'Hi'
            ],
            'sort' => ['-name']
        ]);

        $this->assertEquals(
            'SELECT * FROM users WHERE `name` = :p0 ORDER BY `name` DESC',
            $query->makeSql()
        );
    }

    public function testIsProcessCustomQueryHandlers()
    {
        $paginator = new Paginator;
        $query = (new Query)->table('users');

        $paginator->processQueryHandlers([
            'query' => $query,
            'filter' => [
                'name' => 'Hi'
            ],
            'sort' => ['-name'],
            'filterHandlers' => [
                'name' => function (Query $query, $value) {
                    $query->where('CONCAT(fist_name, last_name)', 'LIKE', '%' . $value . '%');
                }
            ],
            'sortHandlers' => [
                'name' => function (Query $query, $direction) {
                    $query->orderBy('CONCAT(fist_name, last_name)', $direction);
                }
            ]
        ]);

        $this->assertEquals(
            'SELECT * FROM users WHERE CONCAT(fist_name, last_name) LIKE :p0 ORDER BY CONCAT(fist_name, last_name) DESC',
            $query->makeSql()
        );
    }
}
