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

    /**
     * @var AbstractCommand[]
     */
    private $frameworkCommands = [];

    /**
     * @var AbstractCommand[]
     */
    private $applicationCommands = [];

    /**
     * @var bool
     */
    private $showHelp;

    public function __construct(Kernel $kernel, array $options = [])
    {
        define('IS_CONSOLE', true);
        $this->kernel = $kernel;
        $this->showHelp = isset($options['showHelp']) ? $options['showHelp'] : true;
    }

    public function setUp()
    {
        $this->kernel->loadConfiguration();
        $this->parseFrameworkCommands();
        $this->parseApplicationCommands();
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
            $this->frameworkCommands[$command->getName()] = $command;
        }
    }

    private function parseApplicationCommands()
    {
        $pattern  = $this->kernel->getCommandsPath() . '/*.php';

        foreach (glob($pattern) as $file) {
            if (! is_file($file) || strstr($file, 'Abstract')) {
                continue;
            }

            $pieces = explode(DIRECTORY_SEPARATOR, $file);
            $name = str_replace('.php', '', $pieces[count($pieces) - 1]);
            $className = 'App\\Commands\\' . $name;

            $command = new $className;
            $this->commands[$command->getName()] = $command;
            $this->applicationCommands[$command->getName()] = $command;
        }
    }

    private function help()
    {
        if (! $this->showHelp) return;

        $c = new \Colors\Color();

        echo $c('Mini-Fwk')->green() . PHP_EOL . PHP_EOL;


        $labels = [
            'Framework commands:' => $this->frameworkCommands,
            'Application commands:' => $this->applicationCommands
        ];

        foreach ($labels as $label => $commands) {
            echo $c($label)->yellow() . PHP_EOL;
            foreach ($commands as $name => $command) {
                $tabLength = floor(strlen($name) / 8);
                echo $c($name)->green() . str_repeat("\t", 4 - $tabLength) . $command->getDescription() . PHP_EOL;
            }
            echo PHP_EOL;
        }

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

        $helpRequested = in_array('--help', $_SERVER['argv']) ||
            in_array('-h', $_SERVER['argv']);

        if ($helpRequested && ! $this->showHelp) {
            exit;
        }

        $commando = new Commando;
        $commando->setHelp($command->getDescription());
        $command->setKernel($this->kernel);
        $command->setUp($commando);

        if (! $helpRequested) {
            $command->run($commando);
        }
    }
}
