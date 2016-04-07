<?php

namespace Mini\Console\Command;

use Mini\Console\Common\ConsoleTable;
use Commando\Command as Commando;
use Mini\Router\Router;
use Mini\Workers\WorkerQueue;


class RouteListCommand extends AbstractCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'route:list';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'List available routes';
    }

    /**
     * @param Commando $commando
     */
    public function setUp(Commando $commando)
    {
    }

    /**
     * @param Commando $commando
     */
    public function run(Commando $commando)
    {
        $table = new ConsoleTable();
        $table
            ->addHeader('Method')
            ->addHeader('Uri')
            ->addHeader('Controller')
            ->addHeader('Middlewares');

        foreach (Router::listRoutes() as $route) {
            $table->addRow()
                ->addColumn($route['method'])
                ->addColumn($route['uri'])
                ->addColumn($route['controller'])
                ->addColumn(implode(', ', $route['middlewares']));
        }

        $table->display();
    }
}
