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
