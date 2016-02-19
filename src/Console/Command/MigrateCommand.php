<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Container;
use Exception;

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

        $commando->option('rollback-all')
            ->describedAs('Rollback all migrations')
            ->boolean();

        $commando->option('rollback')
            ->describedAs('Rollback last migration')
            ->boolean();

        $commando->option('cleanup')
            ->describedAs('Rollback all migrations then migrate again')
            ->boolean();
    }

    public function run(Commando $commando)
    {
        $c = new \Colors\Color();
        $startTime = microtime();

        $this->ensureVersionsTableIsCreated();

        if ($commando['rollback']) {
            $count = $this->rollbackLastMigration();
        } else if ($commando['rollback-all']) {
            $this->confirm();
            $count = $this->runMigrations('down');
        } else if ($commando['cleanup']) {
            $this->confirm();
            $this->runMigrations('down');
            $count = $this->runMigrations('up');
        } elseif (! $commando['version']) {
            $count = $this->runMigrations('up');
        } else {
            $count = $this->runMigrationByVersion($commando['version'], 'up');
        }

        if ($count > 0) {
            echo $c(
                'Migration completed in ' . number_format((microtime() - $startTime) / 1000, 5) . ' seconds.'
            )->green() . PHP_EOL;
        } else {
            echo $c('Nothing to migrate.')->yellow() . PHP_EOL;
        }
    }

    public function runMigrations($direction = 'up')
    {
        $count = 0;

        $previousVersions = array_map(
            function ($row) {
                return $row['version'];
            },
            $this->model->select(
                'SELECT version FROM migrations ORDER BY version ' . ($direction == 'down' ? 'DESC' : 'ASC')
            )
        );

        if ($direction == 'up') {
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
                    $this->runMigrationByVersion($version, $direction);
                    ++$count;
                }
            }
        } elseif ($direction == 'down') {
            foreach ($previousVersions as $version) {
                $this->runMigrationByVersion($version, $direction);
                ++$count;
            }
        }

        return $count;
    }

    public function rollbackLastMigration()
    {
        $migration = $this->model->selectOne('select version from migrations order by version desc limit 1');

        if (! $migration) {
            throw new Exception('No migration to rollback.');
        }

        $this->runMigrationByVersion($migration['version'], 'down');

        return 1;
    }

    public function runMigrationByVersion($version, $direction = 'up')
    {
        $className = 'Migration' . $version;

        $file = $this->kernel->getMigrationsPath() . DIRECTORY_SEPARATOR . $className . '.php';

        include_once $file;

        echo ($direction == 'up' ? 'Migrating ' : 'Rollback version ') . $version . PHP_EOL;

        $instance = new $className;
        $instance->run($direction);

        if ($direction == 'up') {
            $stm = $this->model->prepare('INSERT INTO migrations(version) VALUES (?)');
        } elseif ($direction = 'down') {
            $stm = $this->model->prepare('DELETE FROM migrations WHERE version LIKE ?');
        }

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
