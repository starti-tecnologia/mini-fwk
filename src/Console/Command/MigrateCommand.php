<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Container;

class MigrateCommand extends AbstractCommand
{
    private $model;

    public function getName()
    {
        return 'migrate';
    }

    public function getDescription()
    {
        return 'Run pending migrations';
    }

    public function setUp(Commando $commando)
    {
        $container = app();
        $this->kernel = $container->get('Mini\Kernel');
        $this->model = $container->get('Mini\Entity\Model');

        $commando->option('version')
            ->describedAs('Migration version to execute, ex: 20160216164758')
            ->defaultsTo('');
    }

    public function run(Commando $commando)
    {
        $startTime = microtime();

        $this->ensureVersionsTableIsCreated();

        if (! $commando['version']) {
            $completedMigrations = $this->runPendingMigrations();
        } else {
            $completedMigrations = $this->runMigrationVersion($commando['version']);
        }

        if ($completedMigrations > 0) {
            echo 'Migration completed in ' . number_format((microtime() - $startTime) / 1000, 5) . ' seconds' . PHP_EOL;
        } else {
            echo 'Nothing to migrate' . PHP_EOL;
        }
    }

    public function runPendingMigrations()
    {
        $count = 0;

        $previousVersions = array_map(function ($row) {
            return $row['version'];
        }, $this->model->select('SELECT version FROM migrations ORDER BY version'));

        $pattern = $this->kernel->getMigrationsPath() . DIRECTORY_SEPARATOR . 'Migration*.php';
        $files = glob($pattern);
        sort($files);

        foreach ($files as $file) {
            $matches = null;

            if (! preg_match('/.+Migration([0-9]+).+$/', $file, $matches)) {
                continue;
            }

            $version = $matches[1];

            if (! in_array($version, $previousVersions)) {
                $this->runMigrationVersion($version);
                ++$count;
            }
        }

        return $count;
    }

    public function runMigrationVersion($version)
    {
        $className = 'Migration' . $version;
        $file = $this->kernel->getMigrationsPath() . DIRECTORY_SEPARATOR . $className . '.php';

        include_once $file;

        echo 'Migrating version ' . $version . PHP_EOL;

        $instance = new $className;
        $instance->run();

        $stm = $this->model->prepare('INSERT INTO migrations(version) VALUES (?)');
        $stm->execute([$version]);

        return 1;
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
        $result = $this->model->select('SELECT TABLE_NAME FROM information_schema.TABLES');
        return count($result) == 1;
    }

    public function createMigrationTable()
    {
        $sql = 'CREATE TABLE migrations (version VARCHAR(255) PRIMARY KEY)';
        $this->model->exec($sql);
    }
}
