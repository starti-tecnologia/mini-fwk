<?php

use Mini\Entity\ConnectionManager;
use Mini\Entity\Behaviors\SqlBuilderAware;

class FakeStatement
{
    private $context;

    public function __construct(array $context)
    {
        $this->context = $context;
    }

    public function execute($parameters = [])
    {
        $this->context['manager']->log[] = [
            $this->context['connection'],
            $this->context['sql'],
            $parameters
        ];
    }

    public function fetch()
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }

        return ['lala' => 'hi'];
    }

    public function fetchObject($className)
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }

        $instance = new $className;
        $instance->lala = 'hi';

        return $instance;
    }

    public function fetchAll($fethStyle=null, $className=null)
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return [];
        }

        $rows = [
            ['lala' => 'hi'],
            ['lala' => 'good day']
        ];

        $results = [];

        foreach ($rows as $row) {
            if ($fethStyle === \PDO::FETCH_ASSOC) {
                $results[] = $row;
            } else if ($fethStyle == \PDO::FETCH_CLASS) {
                $instance = new $className;
                foreach ($row as $key => $value) {
                    $instance->$key = $value;
                }
                $results[] = $instance;
            }
        }

        return $results;
    }
}

class FakeConnection
{
    use SqlBuilderAware;

    private $context;

    public function __construct(array $context)
    {
        $this->context = $context;
    }

    public function prepare($sql)
    {
        return new FakeStatement(array_merge($this->context, ['sql' => $sql]));
    }

    public function lastInsertId()
    {
        return 1;
    }
}

class FakeConnectionManager extends ConnectionManager
{
    public $log;

    public function __construct()
    {
        $this->log = [];
    }

    public function getConnection($name)
    {
        return new FakeConnection([
            'connection' => $name,
            'manager' => $this
        ]);
    }
}
