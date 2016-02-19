<?php

namespace Mini\Entity\Migration;

use Mini\Container;
use Mini\Entity\Connection;

abstract class AbstractMigration
{
    /**
     * @var Mini\Entity\Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $sqls = [];

    abstract function up();

    abstract function down();

    public function addSql($sql, $params = [])
    {
        $this->sqls[] = [$sql, $params];
    }

    public function run($direction = 'up')
    {
        $this->{$direction}();

        foreach ($this->sqls as $sql) {
            $stm = $this->connection->prepare($sql[0]);
            $stm->execute($sql[1]);

            echo 'Executing: ' . $sql[0] . ' ' . json_encode($sql[1]) . PHP_EOL;
        }
    }

    /**
     * @params \Mini\Entity\Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
