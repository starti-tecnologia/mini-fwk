<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class ServeCommand extends AbstractCommand
{
    /**
     * @var LoopInterface
     */
    private $loop;

    public function getName()
    {
        return 'serve';
    }

    public function getDescription()
    {
        return 'Starts PHP builtin server';
    }

    public function setUp(Commando $commando)
    {
        $commando->option('h')
            ->aka('host')
            ->describedAs('Host')
            ->defaultsTo('localhost');

        $commando->option('p')
            ->aka('port')
            ->describedAs('Port')
            ->defaultsTo('8000');
    }

    public function run(Commando $commando)
    {
        $this->loop = Factory::create();

        $this->runServer($commando);
        $this->runMonitor();

        $this->loop->run();
    }

    private function runServer(Commando $commando)
    {
        $basePath = $this->kernel->getBasePath();
        $path = $basePath . DIRECTORY_SEPARATOR . 'server.php';
        $host = $commando['h'];
        $port = $commando['p'];

        return $this->runCommand(sprintf('php -S %s:%s %s', $host, $port, $path));
    }

    private function runMonitor()
    {
        $self = $this;
        $kernel = $this->kernel;
        $commandTimeMap = [];
        $patterns = [
            './console route:scan' => $kernel->getControllersPath(),
            './console worker --scan' => $kernel->getWorkersPath()
        ];

        $this->loop->addPeriodicTimer(1, function () use (&$self, &$patterns, &$kernel, &$commandTimeMap) {
            clearstatcache();
            $isFirstLoop = count($commandTimeMap) === 0;

            foreach ($patterns as $command => $directory) {
                $oldModifyTime = isset($commandTimeMap[$command]) ? $commandTimeMap[$command] : -1;
                $maxModifyTime = $oldModifyTime;
                $files = $self->findFiles($directory);
                foreach ($files as $path) {
                    $modifyTime = @filemtime($path);
                    $isModified = $modifyTime > $maxModifyTime;
                    if ($isModified) {
                        $maxModifyTime = $modifyTime;
                    }
                    if ($isModified && ! $isFirstLoop) {
                        $self->write('Detected change on "'. str_replace($self->kernel->getBasePath(), '.', $path) . '"');
                    }
                }
                if ($maxModifyTime > $oldModifyTime) {
                    $self->runCommand($command);
                    $commandTimeMap[$command] = time();
                }
            }
        });
    }

    /**
     * Run a shell command
     *
     * @param string $cmd
     * @return void
     */
    private function runCommand($cmd)
    {
        $this->write("Running \"$cmd\"");
        $process = new Process($cmd, $this->kernel->getBasePath(), $_ENV);
        $process->start($this->loop);
        $process->stdout->on('data', function ($data) {
            fwrite(STDOUT, $data);
        });
        $process->stderr->on('data', function ($data) {
            fwrite(STDERR, $data);
        });
        return $process;
    }

    private function findFiles($directory, $expectedExtension = 'php', &$results = [])
    {
        $directory = realpath($directory);
        if (! is_dir($directory)) {
            return $results;
        }
        $paths = glob($directory . DIRECTORY_SEPARATOR . '*');
        foreach ($paths as $path) {
            if (! is_dir($path)) {
                $actualExtension = pathinfo($path, PATHINFO_EXTENSION);
                if ($actualExtension == $expectedExtension) {
                    $results[] = $path;
                }
            } else {
                $this->findFiles($path, $expectedExtension, $results);
            }
        }

        return $results;
    }
}
