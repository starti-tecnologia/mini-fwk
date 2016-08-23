<?php

use Mini\Entity\Query;
use Mini\Entity\RawValue;
use Mini\Exceptions\QueryException;

class QueryTest extends PHPUnit_Framework_TestCase
{
    private $connectionManager;

    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/FakeConnectionManager.php';
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/ReversedRelationEntityStub.php';

        $this->connectionManager = new FakeConnectionManager;

        app()->register('Mini\Entity\ConnectionManager', function () {
            return $this->connectionManager;
        });
    }

    public function testIsMakingSimpleSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users`',
            (new Query)
                ->table('users')
                ->makeSql()
        );
    }

    public function testIsMakingRawTableSql()
    {
        $this->assertEquals(
            'SELECT * FROM (SELECT * FROM logins) `t`',
            (new Query)
                ->rawTable('SELECT * FROM logins')
                ->makeSql()
        );
    }

    public function testIsMakingSubQueryTableSql()
    {
        $query = (new Query)
            ->table(
                (new Query)
                    ->select(['id'])
                    ->table('users')
                    ->where('name', '=', 'Test')
                    ->limit(0, 1)
            );

        $this->assertEquals(
            'SELECT * FROM (SELECT `id` FROM `users` WHERE `name` = :s0p0 LIMIT 0, 1) `users`',
            $query->makeSql()
        );

        $this->assertEquals(
            ['s0p0' => 'Test'],
            $query->spec['bindings']
        );
    }

    public function testIsMakingSubQueryJoinSql()
    {
        $query = (new Query)
            ->table('users')
            ->alias('u')
            ->innerJoin(
                (new Query)
                    ->select(['picture'])
                    ->table('profiles')
                    ->alias('sp')
                    ->where('id', '=', 1),
                'sp.id',
                '=',
                'u.id'
            );

        $this->assertEquals(
            'SELECT * FROM `users` `u` INNER JOIN (SELECT `picture` FROM `profiles` `sp` WHERE `id` = :s0p0) `sp` ON (`sp`.`id` = `u`.`id`)',
            $query->makeSql()
        );

        $this->assertEquals(
            ['s0p0' => 1],
            $query->spec['bindings']
        );
    }

    public function testIsMakingSelectSql()
    {
        $this->assertEquals(
            'SELECT `name`, `age`, `deleted_at` FROM `users`',
            (new Query)
                ->table('users')
                ->select(
                    ['name', 'age']
                )
                ->addSelect(
                    ['deleted_at']
                )
                ->makeSql()
        );
    }

    public function testIsMakingLeftJoinSql()
    {
        $this->assertEquals(
            'SELECT `name` FROM `users` LEFT JOIN `user_types` ON (`user_types`.`id` = `users`.`id`)',
            (new Query)
                ->table('users')
                ->select(['name'])
                ->leftJoin('user_types', 'user_types.id', '=', 'users.id')
                ->makeSql()
        );
    }

    public function testIsMakingInnerJoinSql()
    {
        $this->assertEquals(
            'SELECT `name` FROM `users` INNER JOIN `user_types` ON (`user_types`.`id` = `users`.`id`)',
            (new Query)
                ->table('users')
                ->select(['name'])
                ->innerJoin('user_types', 'user_types.id', '=', 'users.id')
                ->makeSql()
        );
    }

    public function testIsMakingWhereSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` = :p0',
            (new Query)
                ->table('users')
                ->where('name', '=', 'Lala')
                ->makeSql()
        );
    }

    public function testIsMakingWhereWithOperatorSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` = :p0 OR `deleted_at` < NOW()',
            (new Query)
                ->table('users')
                ->where('name', '=', 'Lala')
                ->where('deleted_at', '<', new RawValue('NOW()'), 'OR')
                ->makeSql()
        );
    }

    public function testIsMakingWhereWithSubQuerySql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` = :p0 OR (`name` = :p1 OR `x` IN (:p2) OR `deleted_at` < NOW())',
            (new Query)
                ->table('users')
                ->where('name', '=', 'Lala')
                ->where(
                    (new Query())
                        ->where('name', '=', 'Lala')
                        ->where('x', 'IN', [1], 'OR')
                        ->where('deleted_at', '<', new RawValue('NOW()'), 'OR'),
                    'OR'
                )
                ->makeSql()
        );

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` = :p0 AND (`name` = :p1)',
            (new Query)
                ->table('users')
                ->where('name', '=', 'Lala')
                ->where(
                    (new Query())
                        ->where('name', '=', 'Lala')
                )
                ->makeSql()
        );
    }

    public function testIsMakingWhereInSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` IN (:p0, :p1)',
            (new Query)
                ->table('users')
                ->where('name', 'IN', ['Jonh', 'James'])
                ->makeSql()
        );
    }

    public function testIsMakingWhereIsNullSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` IS NULL',
            (new Query)
                ->table('users')
                ->whereIsNull('name')
                ->makeSql()
        );
    }

    public function testIsMakingWhereIsNotNullSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `name` IS NOT NULL',
            (new Query)
                ->table('users')
                ->whereIsNotNull('name')
                ->makeSql()
        );
    }

    public function testIsMakingOrderBySql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` ORDER BY `name` ASC',
            (new Query)
                ->table('users')
                ->orderBy('name', 'ASC')
                ->makeSql()
        );
    }

    public function testIsMakingGroupBySql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` GROUP BY `name` ORDER BY `name` ASC',
            (new Query)
                ->table('users')
                ->orderBy('name', 'ASC')
                ->groupBy('name')
                ->makeSql()
        );

        $this->assertEquals(
            'SELECT * FROM `users` GROUP BY `name`, `age` ORDER BY `name` ASC',
            (new Query)
                ->table('users')
                ->orderBy('name', 'ASC')
                ->groupBy(['name', 'age'])
                ->makeSql()
        );
    }

    public function testIsMakingLimitSql()
    {
        $this->assertEquals(
            'SELECT * FROM `users` LIMIT 0, 1000',
            (new Query)
                ->table('users')
                ->limit(0, 1000)
                ->makeSql()
        );
    }

    public function testIsMakingRequiredIncludeRelationSql()
    {
        $this->assertEquals(
            'SELECT `posts`.*, owner.name as owner_name FROM `posts` INNER JOIN `users` `owner` ON (`posts`.`owner_id` = `owner`.`id`)',
            RelationEntityStub::query()
                ->includeRelation('owner')
                ->makeSql()
        );
    }

    public function testIsMakingNotRequiredIncludeRelationSql()
    {
        $this->assertEquals(
            'SELECT `posts`.*, owner.name as owner_name FROM `posts` LEFT JOIN `users` `owner` ON (`posts`.`owner_id` = `owner`.`id`)',
            RelationEntityStub::query()
                ->includeRelation('owner', false)
                ->makeSql()
        );
    }

    public function testIsMakingIncludeRelationWithFieldsSql()
    {
        $this->assertEquals(
            'SELECT `posts`.*, owner.name as owner_name FROM `posts` LEFT JOIN `users` `owner` ON (`posts`.`owner_id` = `owner`.`id`)',
            RelationEntityStub::query()
                ->includeRelation('owner', false, ['name'])
                ->makeSql()
        );

        $this->assertEquals(
            'SELECT `posts`.* FROM `posts` LEFT JOIN `users` `owner` ON (`posts`.`owner_id` = `owner`.`id`)',
            RelationEntityStub::query()
                ->includeRelation('owner', false, [])
                ->makeSql()
        );
    }

    public function testIsMakingIncludeRelationReversed()
    {
        $this->assertEquals(
            'SELECT `posts`.*, reversed.id as reversed_id, reversed.name as reversed_name, reversed.relation_id as reversed_relation_id FROM `posts` INNER JOIN `reverseds` `reversed` ON (`reversed`.`relation_id` = `posts`.`id`)',
            RelationEntityStub::query()
                ->includeRelation('reversed')
                ->makeSql()
        );
    }

    public function testIsMakingFullSql()
    {
        $this->assertEquals(
            'SELECT `name`, `age` FROM `users` INNER JOIN `a` ON (`a`.`id` = `users`.`id`) LEFT JOIN `b` ON (`b`.`id` = `users`.`id`) WHERE `x` = :p0 AND `y` = NOW() OR `z` <= :p1 ORDER BY `age` ASC, `name` ASC LIMIT 0, 1000',
            (new Query)
                ->table('users')
                ->select(['name'])
                ->addSelect(['age'])
                ->innerJoin('a', 'a.id', '=', 'users.id')
                ->leftJoin('b', 'b.id', '=', 'users.id')
                ->where('x', '=', 'A')
                ->where('y', '=', new RawValue('NOW()'))
                ->where('z', '<=', 3, 'OR')
                ->limit(0, 1000)
                ->orderBy('age')
                ->orderBy('name', 'ASC')
                ->makeSql()
        );
    }

    public function testIsMakingAliasSql()
    {
        $this->assertEquals(
            'SELECT `p`.*, owner.name as owner_name FROM `posts` `p` INNER JOIN `users` `owner` ON (`p`.`owner_id` = `owner`.`id`)',
            RelationEntityStub::query()
                ->alias('p')
                ->includeRelation('owner')
                ->makeSql()
        );
    }

    public function testIsReplacingAliasSql()
    {
        $this->assertEquals(
            'SELECT * FROM `posts` `p` WHERE `p`.`deleted_at` IS NULL',
            RelationEntityStub::query()
                ->whereIsNull('posts.deleted_at')
                ->alias('p')
                ->makeSql()
        );
    }

    public function testIsGettingObject()
    {
        $result = (new Query)
            ->connection('default')
            ->className(EntityStub::class)
            ->table('users')
            ->getObject();

        $this->assertEquals('hi', $result->lala);
        $this->assertTrue($result instanceof EntityStub);
    }

    public function testIsGettingObjectOrFailing()
    {
        $exception = null;

        try {
            $result = (new Query)
                ->connection('default')
                ->className(EntityStub::class)
                ->table('FAKE_CONNECTION_EMPTY_TABLE')
                ->getObjectOrFail();
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertEquals('No query results for entity EntityStub', $exception->getMessage());
    }

    public function testIsGettingArray()
    {
        $result = (new Query)
            ->connection('default')
            ->className(EntityStub::class)
            ->table('users')
            ->getArray();

        $this->assertEquals(['lala' => 'hi'], $result);
    }

    public function testIsGettingArrayOrFailing()
    {
        $exception = null;

        try {
            $result = (new Query)
                ->connection('default')
                ->className(EntityStub::class)
                ->table('FAKE_CONNECTION_EMPTY_TABLE')
                ->getArrayOrFail();
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertEquals(
            'No query results for table FAKE_CONNECTION_EMPTY_TABLE',
            $exception->getMessage()
        );
    }

    public function testIsListingObject()
    {
        $results = (new Query)
            ->connection('default')
            ->className(EntityStub::class)
            ->table('users')
            ->listObject();

        $this->assertEquals('hi', $results[0]->lala);
        $this->assertTrue($results[0] instanceof EntityStub);
    }

    public function testIsListingArray()
    {
        $results = (new Query)
            ->connection('default')
            ->className(EntityStub::class)
            ->table('users')
            ->listArray();

        $this->assertEquals(['lala' => 'hi'], $results[0]);
    }
}
