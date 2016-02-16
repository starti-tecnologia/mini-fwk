<?php

namespace Mini\Entity\Migration;

use Mini\Container;

abstract class AbstractMigration
{
    /**
     * @var Mini\Entity\Model
     */
    private $model;

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
        $this->model = app()->get('Mini\Entity\Model');
        $this->{$direction}();

        foreach ($this->sqls as $sql) {
            $stm = $this->model->prepare($sql[0]);
            $stm->execute($sql[1]);

            echo 'Executing: ' . $sql[0] . ' ' . json_encode($sql[1]) . PHP_EOL;
        }
    }
}
