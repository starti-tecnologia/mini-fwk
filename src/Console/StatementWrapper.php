<?php

namespace Mini\Console;

class StatementWrapper
{
    public $isVerbose = null;

    public $sql = null;

    private $statement = null;

    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    public function __call($name, $arguments)
    {
        if ($name === 'execute' && $this->isVerbose) {
            echo sprintf('Executing: %s', $this->sql) . PHP_EOL;
        }

        return call_user_func_array([$this->statement, $name], $arguments);
    }
}
