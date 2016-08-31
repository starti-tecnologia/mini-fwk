<?php

namespace Mini\Helpers\Fake;

use Mini\Entity\ConnectionManager;
use Mini\Entity\Behaviors\SqlBuilderAware;

class FakeConnectionManager extends ConnectionManager
{
    public $log;

    public $fixtures = []; // Array in format 'regex' => [results]

    private static $instance;

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
            'fixtures' => & $this->fixtures
        ]);
    }

    public function reset($fixtures = [])
    {
        $this->fixtures = $fixtures;
        $this->log = [];
    }

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}
