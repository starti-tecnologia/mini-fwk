<?php

use Mini\Entity\Query;
use Mini\Entity\Pagination\MariaDBPaginator;
use Mini\Entity\RawValue;

class MariaDBPaginatorTest extends PHPUnit_Framework_TestCase
{
    public function testIsCreatingSimplePaginatorSql()
    {
        $paginator = new MariaDBPaginator;
        $query = (new Query)
            ->select(['name'])
            ->table('users');

        $sqls = $paginator->createPaginatorSelects([
            'sql' => $query->makeSql(),
            'from' => $query->makeFromSql(),
            'columnsQuantity' => 1,
            'page' => 1,
            'perPage' => 20
        ]);

        $this->assertEquals(
            'SELECT `name` FROM `users` LIMIT 0,20',
            $sqls[0]
        );

        $this->assertEquals(
            'SELECT COUNT(1) FROM `users`',
            $sqls[1]
        );
    }

    public function testIsCreatingSimplePaginatorSqlWithSubqueryColumns()
    {
        $paginator = new MariaDBPaginator;
        $query = (new Query)
            ->select([
                'name',
                '(SELECT COUNT(1) FROM comments WHERE user_id = users.id) as total'
            ])
            ->where('name', 'LIKE', 'A')
            ->table('users');

        $sqls = $paginator->createPaginatorSelects([
            'sql' => $query->makeSql(),
            'from' => $query->makeFromSql(),
            'columnsQuantity' => 1,
            'page' => 1,
            'perPage' => 20
        ]);

        $this->assertEquals(
            'SELECT `name`, (SELECT COUNT(1) FROM comments WHERE user_id = users.id) as total FROM `users` WHERE `name` LIKE :p0 LIMIT 0,20',
            $sqls[0]
        );

        $this->assertEquals(
            'SELECT COUNT(1) FROM `users` WHERE `name` LIKE :p0',
            $sqls[1]
        );
    }
}
