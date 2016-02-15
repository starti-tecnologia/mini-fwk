<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;

class ServeCommand extends AbstractCommand
{
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
        $basePath = $this->kernel->getBasePath();
        $path = $basePath . DIRECTORY_SEPARATOR . 'server.php';
        $host = $commando['h'];
        $port = $commando['p'];

        $cmd = sprintf('php -S %s:%s %s', $host, $port, $path);
        echo $cmd . PHP_EOL;
        passthru($cmd);
    }
}
