<?php

namespace Mini\Console;

class ConnectionWrapper
{
    public $isVerbose = null;

    private $connection = null;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function prepare($sql)
    {
        $wrapper = new StatementWrapper($this->connection->prepare($sql));
        $wrapper->sql = $sql;
        $wrapper->isVerbose = $this->isVerbose;

        return $wrapper;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->connection, $name], $arguments);
    }
}
