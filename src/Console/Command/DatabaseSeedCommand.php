<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Entity\DatabaseSeed\DatabaseSeeder;

class DatabaseSeedCommand extends AbstractCommand
{
    private $yes = false;

    public function getName()
    {
        return 'db:seed';
    }

    public function getDescription()
    {
        return 'Populate initial or test data';
    }

    public function setUp(Commando $commando)
    {
        $commando->option('initial')
            ->describedAs('Populate initial data')
            ->boolean();

        $commando->option('test')
            ->describedAs('Populate test data')
            ->boolean();

        $commando->option('yes')
            ->aka('y')
            ->describedAs('Populate test data')
            ->boolean();
    }

    public function run(Commando $commando)
    {
        $initial = $commando['initial'];
        $test = $commando['test'];
        $this->yes = $commando['yes'];

        if (! $initial && ! $test) {
            throw new \Exception('You must specify --initial or --test');
        }

        if ($initial) {
            $this->populateData('initial');
        }

        if ($test) {
            $this->populateData('test');
        }
    }

    private function populateData($type)
    {
        $kernel = app()->get('Mini\Kernel');
        $seeder = new DatabaseSeeder($kernel->getSeedsPath(), $type);
        $seeder->connectionManager = app()->get('Mini\Entity\ConnectionManager');

        $seeder->loadData();

        if (! $this->yes) {
            $this->confirm(
                'That can erase data in the following tables: ' .
                implode(', ', $seeder->getTableNames()) . '. Are you sure?'
            );
        }

        $seeder->execute(true);
    }
}
