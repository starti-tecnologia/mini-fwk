<?php

namespace Mini\Helpers\Fake;

use Mini\Entity\ConnectionManager;
use Mini\Entity\Behaviors\SqlBuilderAware;
use PHPUnit_Framework_Assert as Assertions;

class FakeConnectionManager extends ConnectionManager
{
    public $log;

    public $fixtures = []; // Array in format 'regex' => [results]

    public $calls = []; // Calls in format ['method' => MethodName, 'arguments' => [arguments]]

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
            'fixtures' => & $this->fixtures,
            'calls' => & $this->calls
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

    public function assertCall($method, array $arguments = [])
    {
        $match = null;

        foreach ($this->calls as $call) {

            if ($method != $call['method'] || $arguments != array_slice($call['arguments'], 0, count($arguments))) {
                continue;
            }
            $match = $call;
        }

        Assertions::assertNotNull(
            $match,
            sprintf(
                "Expected %s call with following arguments %s\nBut received only the following calls %s",
                $method,
                print_r($arguments, true),
                print_r($this->calls, true)
            )
        );
    }
}
