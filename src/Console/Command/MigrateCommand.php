<?php

namespace Mini\Console\Command;

use Mini\Entity\Migration\AbstractMigration;
use Mini\Entity\Connection;
use Commando\Command as Commando;
use Mini\Container;
use Exception;

class MigrateCommand extends AbstractCommand
{
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
        $startTime = microtime(true);

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
            $count = $this->runMigration($this->getMigrationByVersion($commando['version']), 'up', $commando['version']);
        }

        if ($count > 0) {
            echo $c(
                'Migration completed in ' . number_format((microtime(true) - $startTime), 5) . ' seconds.'
            )->green() . PHP_EOL;
        } else {
            echo $c('Nothing to migrate.')->yellow() . PHP_EOL;
        }
    }

    /**
     * Rull all pending migrations
     */
    public function runMigrations($direction = 'up')
    {
        $count = 0;

        $migrations = $this->loadMigrations($direction);

        foreach ($migrations as $version => $migration) {
            $isApplied = $this->checkIfMigrationIsApplied($migration, $version);

            if ($direction == 'up' && !$isApplied || $direction == 'down' && $isApplied) {
                $this->runMigration($migration, $direction, $version);
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Run one specific migration
     * 
     * @var AbstractMigration $migration
     * @return integer
     */
    public function runMigration(AbstractMigration $migration, $direction, $version)
    {
        echo ($direction == 'up' ? 'Migrating ' : 'Rollback version ') . $version . PHP_EOL;

        $migration->run($direction);

        if ($direction == 'up') {
            $stm = $migration->getConnectionInstance()->prepare('INSERT INTO migrations(version) VALUES (?)');
        } elseif ($direction = 'down') {
            $stm = $migration->getConnectionInstance()->prepare('DELETE FROM migrations WHERE version LIKE ?');
        }

        $stm->execute([$version]);

        return 1;
    }

    /**
     * Rollback last executed migration
     */
    public function rollbackLastMigration()
    {
        $c = new \Colors\Color();
        $migrations = $this->loadMigrations('down');
        $success = false;

        foreach ($migrations as $version => $migration) {
            if ($this->checkIfMigrationIsApplied($migration, $version) === true) {
                $this->runMigration($migration, 'down', $version);
                $success = true;
                break;
            }
        }

        if (! $success) {
            echo $c('No migration to rollback.')->white()->bold() . PHP_EOL;
            return 0;
        } else {
            return 1;
        }
    }


    private function checkIfMigrationIsApplied(AbstractMigration $migration, $version)
    {
        $row = $migration->getConnectionInstance()->selectOne(
            'SELECT COUNT(1) as total FROM migrations WHERE version = :version',
            [
                'version' => $version
            ]
        );

        return $row['total'] == 1;
    }

    private function loadMigrations($order = 'up')
    {
        $migrationMap = [];

        $pattern = $this->kernel->getMigrationsPath() . DIRECTORY_SEPARATOR . 'Migration*.php';
        $files = glob($pattern);
        sort($files);

        if ($order == 'down') {
            $files = array_reverse($files);
        }

        foreach ($files as $file) {
            $matches = null;

            if (! preg_match('/.+Migration([0-9]+).+$/', $file, $matches)) {
                continue;
            }

            $version = $matches[1];

            $migrationMap[$version] = $this->getMigrationByVersion($version);
        }

        return $migrationMap;
    }

    private function getMigrationByVersion($version)
    {
        $className = 'Migration' . $version;
        $file = $this->kernel->getMigrationsPath() . DIRECTORY_SEPARATOR . $className . '.php';
        include_once $file;
        return new $className;
    }
}
