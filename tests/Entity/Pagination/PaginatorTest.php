<?php

use Mini\Entity\Query;
use Mini\Entity\Pagination\Paginator;
use Mini\Entity\RawValue;

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
            'SELECT * FROM `users` WHERE `name` = :p0 ORDER BY `name` DESC',
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
            'SELECT * FROM `users` WHERE CONCAT(fist_name, last_name) LIKE :p0 ORDER BY CONCAT(fist_name, last_name) DESC',
            $query->makeSql()
        );
    }

    public function testIsGeneratingDefaultFormat()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'posts.id',
                'posts.name',
                'posts.owner_will_go'
            ])
            ->className(RelationEntityStub::class)
            ->table('posts')
            ->includeRelation('owner');

        $options = $paginator->processQueryOptions([
            'query' => $query
        ]);

        $this->assertEquals(
            [
                'id|integer',
                'name',
                'owner_will_go|boolean',
                'owner|object|prefix:owner_' => [
                    'id|integer',
                    'name'
                ]
            ],
            $options['format']
        );
    }

    public function testIsGeneratingDefaultFormatConsideringVisibleFields()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'posts.id',
                'posts.name'
            ])
            ->className(RelationEntityStub::class)
            ->table('posts')
            ->includeRelation('owner');

        $owner = new SimpleEntityStub;
        $owner->visible = ['name'];
        $query->getInstance()->relations['owner']['instance'] = $owner;

        $options = $paginator->processQueryOptions([
            'query' => $query
        ]);

        $this->assertEquals(
            [
                'id|integer',
                'name',
                'owner|object|prefix:owner_' => [
                    'name'
                ]
            ],
            $options['format']
        );
    }

    public function testIsGeneratingDefaultFormatConsideringPrefixedObjects()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/PrefixObjectEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'posts.id',
                'posts.name',
                'posts.max_users_quantity',
                'posts.address_geolocalization',
                'posts.address_street_name',
                'posts.address_number'
            ])
            ->className(PrefixObjectEntityStub::class)
            ->table('posts');

        $options = $paginator->processQueryOptions([
            'query' => $query
        ]);

        $this->assertEquals(
            [
                'id|integer',
                'name',
                'max_users_quantity|integer',
                'address|object|prefix:address_|required' => [
                    'geolocalization',
                    'street_name',
                    'number'
                ]
            ],
            $options['format']
        );
    }

    public function testIsGeneratingDefaultFieldFilters()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'posts.id',
                'posts.name'
            ])
            ->className(RelationEntityStub::class)
            ->table('posts')
            ->where('context', '=', 'test')
            ->includeRelation('owner');

        $options = $paginator->processQueryHandlers([
            'query' => $query,
            'filter' => [
                'search' => 'Hi'
            ]
        ]);

        $this->assertEquals(
            [
                ['context', '=', ':p0', 'AND'],
                ['(posts.id', 'LIKE', ':p1', 'AND'],
                ['posts.name', 'LIKE', ':p2', 'OR'],
                ['owner.id', 'LIKE', ':p3', 'OR'],
                ['owner.guid', 'LIKE', ':p4', 'OR'],
                ['owner.name', 'LIKE', ':p5)', 'OR'],
            ],
            $query->spec['wheres']
        );
    }

    public function testIsUsingReplaces()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'context',
                'posts.id',
                'posts.name'
            ])
            ->className(RelationEntityStub::class)
            ->table('posts')
            ->includeRelation('owner');

        $options = $paginator->processQueryHandlers([
            'query' => $query,
            'filter' => [
                'context' => '1',
                'search' => 'hi'
            ],
            'sort' => ['-context'],
            'replaces' => [
                'posts.name' => 'CONCAT(posts.name, owner.name)',
                'context' => ['DATE_FORMAT(context)', '>=']
            ]
        ]);

        $this->assertEquals(
            [
                ['DATE_FORMAT(context)', '>=', ':p0', 'AND'],
                ['(DATE_FORMAT(context)', 'LIKE', ':p1', 'AND'],
                ['posts.id', 'LIKE', ':p2', 'OR'],
                ['CONCAT(posts.name, owner.name)', 'LIKE', ':p3', 'OR'],
                ['owner.id', 'LIKE', ':p4', 'OR'],
                ['owner.guid', 'LIKE', ':p5', 'OR'],
                ['owner.name', 'LIKE', ':p6)', 'OR']
            ],
            $query->spec['wheres']
        );

        $this->assertEquals(
            [
                ['DATE_FORMAT(context)', 'DESC'],
            ],
            $query->spec['orderBy']
        );
    }

    public function testIsGeneratingRawSelectFilters()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/SimpleEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/RelationEntityStub.php';

        $paginator = new Paginator;

        $query = (new Query)
            ->select([
                'posts.id',
                'posts.name',
                'IF(NOW() > (customers_licenses.validate), \'1\', \'0\') AS expirate'
            ])
            ->className(RelationEntityStub::class)
            ->table('posts')
            ->where('context', '=', 'test');

        $options = $paginator->processQueryHandlers([
            'query' => $query,
            'filter' => [
                'search' => 'Hi'
            ]
        ]);

        $this->assertEquals(
            [
                ['context', '=', ':p0', 'AND'],
                ['(posts.id', 'LIKE', ':p1', 'AND'],
                ['posts.name', 'LIKE', ':p2', 'OR'],
                ['IF(NOW() > (customers_licenses.validate), \'1\', \'0\')', 'LIKE', ':p3)', 'OR'],
            ],
            $query->spec['wheres']
        );
    }
}
