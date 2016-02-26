<?php

namespace Mini\Entity\Migration;

use Mini\Container;
use Mini\Entity\Connection;

abstract class AbstractMigration
{
    /**
     * @var string
     */
    public $connection = 'default';

    /**
     * @var Mini\Entity\Connection
     */
    private $connectionInstance;

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
        $pdo = $this->getConnectionInstance();

        $this->ensureVersionsTableIsCreated();

        $this->{$direction}();

        foreach ($this->sqls as $sql) {
            $stm = $pdo->prepare($sql[0]);
            $stm->execute($sql[1]);

            echo 'Executing: ' . $sql[0] . ' ' . json_encode($sql[1]) . PHP_EOL;
        }
    }

    /**
     * @params \Mini\Entity\Connection $connection
     */
    public function getConnectionInstance()
    {
        if (! $this->connectionInstance) {
            $this->connectionInstance = app()->get('Mini\Entity\ConnectionManager')->getConnection($this->connection);
        }

        return $this->connectionInstance;
    }

    private function ensureVersionsTableIsCreated()
    {
        if (! $this->isMigrationTableCreated()) {
            $this->createMigrationTable();
        }
    }

    private function isMigrationTableCreated()
    {
        $sql = <<<SQL
SELECT
    TABLE_NAME
FROM
    information_schema.TABLES
WHERE
    TABLE_NAME = 'migrations'
SQL;
        $result = $this->getConnectionInstance()->select('SELECT TABLE_NAME FROM information_schema.TABLES');
        return count($result) == 1;
    }

    public function createMigrationTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS migrations (version VARCHAR(255) PRIMARY KEY)';
        $this->getConnectionInstance()->exec($sql);
    }
}
