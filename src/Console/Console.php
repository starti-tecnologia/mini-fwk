<?php

namespace Mini\Console;

use Mini\Kernel;
use Commando\Command as Commando;

class Console
{
    private $kernel;

    /**
     * @var AbstractCommand[]
     */
    private $commands = [];

    public function __construct (Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function setUp()
    {
        $this->parseFrameworkCommands();
    }

    private function parseFrameworkCommands()
    {
        $pattern  = __DIR__ . '/Command/*.php';

        foreach (glob($pattern) as $file) {
            if (! is_file($file) || strstr($file, 'Abstract')) {
                continue;
            }

            $pieces = explode(DIRECTORY_SEPARATOR, $file);
            $name = str_replace('.php', '', $pieces[count($pieces) - 1]);
            $className = 'Mini\\Console\\Command\\' . $name;

            $command = new $className;
            $this->commands[$command->getName()] = $command;
        }
    }

    private function help()
    {
        foreach ($this->commands as $name => $command) {
            echo $name . "\t\t" . $command->getDescription() . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function run()
    {
        $argv = $_SERVER['argv'];

        $this->setUp();
        $command = isset($this->commands[$argv[1]]) ? $this->commands[$argv[1]] : null;

        if (! $command) {
            echo 'Command not found: ' . $argv[1] . PHP_EOL;
            $this->help();
            return;
        }

        $commando = new Commando;
        $commando->option()
            ->require()
            ->describedAs($command->getName());

        $command->setKernel($this->kernel);
        $command->setUp($commando);
        $command->run($commando);
    }
}
