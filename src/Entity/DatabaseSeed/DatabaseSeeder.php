<?php

namespace Mini\Entity\DatabaseSeed;

use Mini\Console\ConnectionWrapper;

class DatabaseSeeder
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    public $data;

    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    public function __construct($basePath, $type)
    {
        $this->basePath = $basePath;
        $this->type = $type;
    }

    public function loadData()
    {
        $files = glob($this->basePath . DIRECTORY_SEPARATOR . $this->type . DIRECTORY_SEPARATOR . '*.php');

        $results = [];

        foreach ($files as $file) {
            $pathPieces = explode(DIRECTORY_SEPARATOR, $file);
            $namePieces = explode('.', array_pop($pathPieces));
            $name = array_shift($namePieces);

            $results[$name] = require $file;
        }

        $this->data = $results;

        uasort($this->data, function ($a, $b) {
            $orderA = isset($a['order']) ? $a['order'] : PHP_INT_MAX;
            $orderB = isset($b['order']) ? $b['order'] : PHP_INT_MAX;

            return $orderA == $orderB ? 0 : ($orderA > $orderB ? 1 : -1);
        });
    }

    public function getTableNames()
    {
        return array_keys($this->data);
    }

    public function validate()
    {
        foreach ($this->data as $tableName => $spec) {
            foreach ($spec['rows'] as $row) {
                if (empty($row['id'])) {
                    throw new \Exception('Id is required');
                }
            }
        }
    }

    public function execute($verbose = false)
    {
        $this->loadData();
        $this->validate();

        foreach ($this->data as $tableName => $spec) {
            $connection = $this->connectionManager->getConnection($spec['connection']);
            $connection = new ConnectionWrapper($connection);
            $connection->isVerbose = $verbose;

            $stm = $connection->prepare('SET foreign_key_checks = 0;');
            $stm->execute();

            $ids = [];

            foreach ($spec['rows'] as $row) {
                $ids[] = $row['id'];

                $connection->replace($tableName, $row);
            }

            if (count($ids)) {
                $sql = sprintf(
                    'DELETE FROM ' . $tableName . ' WHERE id NOT IN (%s)',
                    implode(', ', $ids)
                );
                $stm = $connection->prepare($sql);
                $stm->execute();
            }

            $stm = $connection->prepare('SET foreign_key_checks = 1;');
            $stm->execute();
        }
    }
}
