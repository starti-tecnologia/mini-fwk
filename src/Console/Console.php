<?php

namespace Mini\Console;

use Mini\Kernel;
use Commando\Command as Commando;
use Exception;

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
        echo 'Mini-Fwk' . PHP_EOL . PHP_EOL;

        echo 'Available Commands:' . PHP_EOL;

        foreach ($this->commands as $name => $command) {
            $tabLength = floor(strlen($name) / 8);
            echo $name . str_repeat("\t", 4 - $tabLength) . $command->getDescription() . PHP_EOL;
        }

        echo PHP_EOL;
    }

    public function run()
    {
        $c = new \Colors\Color();

        try {
            $this->processRun();
        } catch (Exception $e) {
            echo $c($e->getMessage())->white()->bold()->highlight('red') . PHP_EOL;
            echo $c($e->getTraceAsString())->white()->bold()->highlight('red') . PHP_EOL;
            die;
        }
    }

    public function processRun()
    {
        $this->setUp();
        $commandName = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
        $command = isset($this->commands[$commandName]) ? $this->commands[$commandName] : null;

        unset($_SERVER['argv'][1]);

        $_SERVER['argv'] = array_values($_SERVER['argv']);

        if (! $command) {
            if ($commandName) {
                echo 'Command not found: ' . $commandName . PHP_EOL;
            }

            $this->help();
            return;
        }

        $commando = new Commando;
        $commando->setHelp($command->getDescription());
        $command->setKernel($this->kernel);
        $command->setUp($commando);

        if (! in_array('--help', $_SERVER['argv']) && ! in_array('-h', $_SERVER['argv'])) {
            $command->run($commando);
        }
    }
}
