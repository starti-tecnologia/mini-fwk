<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;

class MakeWorkerCommand extends AbstractCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'make:worker';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Make a worker class';
    }

    /**
     * @param Commando $commando
     */
    public function setUp(Commando $commando)
    {
        $commando->option('name')
            ->describedAs('Worker name, example: "SendEmail"')
            ->required();

        $commando->option('sleepTime')
            ->describedAs('Interval between executions, in seconds')
            ->defaultsTo(10);
    }

    /**
     * @param Commando $commando
     */
    public function run(Commando $commando)
    {
        $path = app()->get('Mini\Kernel')->getWorkersPath();
        $neededPath = $path . '/scanned';
        if (! is_dir($neededPath)) {
            shell_exec('mkdir -p ' . $neededPath);
        }
        $className = ucwords($commando['name']) . 'Worker';
        $file = $path . DIRECTORY_SEPARATOR .  $className . '.php';
        $replaces = [
            'ClassNamePlaceholder' => $className,
            'WorkerNamePlaceholder' => $commando['name'],
            'SleepTimePlaceholder' => $commando['sleepTime'] * 1000
        ];
        $template = file_get_contents(__DIR__ . '/Templates/WorkerTemplate.php');
        file_put_contents(
            $file,
            str_replace(array_keys($replaces), array_values($replaces), $template)
        );
        $this->write('Worker file created at ' . $file, 'green');
        (new WorkerCommand)->scanWorkers();
    }
}
