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

    private function getResults()
    {
        $rows = [
            ['lala' => 'hi'],
            ['lala' => 'good day']
        ];
        foreach ($this->context['fixtures'] as $pattern => $results) {
            if (preg_match($pattern, $this->context['sql'])) {
              $rows = $results;
            }
        }
        return $rows;
    }

    public function fetch()
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }

        return $this->getResults()[0];
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

        $rows = $this->getResults();

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

    public $database = 'test';

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

    public $fixtures = []; // Array in format 'regex' => [results]

    public function __construct($fixtures = [])
    {
        $this->log = [];
        $this->fixtures = $fixtures;
    }

    public function getConnection($name)
    {
        return new FakeConnection([
            'connection' => $name,
            'manager' => $this,
            'fixtures' => $this->fixtures
        ]);
    }
}
