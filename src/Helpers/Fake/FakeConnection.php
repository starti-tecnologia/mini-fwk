<?php

namespace Mini\Helpers\Fake;

use Mini\Entity\Behaviors\SqlBuilderAware;

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

    public function exec($sql)
    {
        return (new FakeStatement(array_merge($this->context, ['sql' => $sql])))
            ->execute();
    }

    public function lastInsertId()
    {
        return 1;
    }
}
