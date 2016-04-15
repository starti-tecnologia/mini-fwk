<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 01/04/16
 * Time: 16:23
 */

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Console\Common\ConsoleTable;
use Mini\Workers\WorkerQueue;
use Mini\Workers\WorkerRunner;
use Notoj\Cache;
use Notoj\File;
use Notoj\Notoj;
use Notoj\ReflectionClass;

class WorkerCommand extends AbstractCommand
{

    public function getName()
    {
        return 'worker';
    }

    public function getDescription()
    {
        return 'Worker job';
    }

    public function setUp(Commando $commando)
    {
        $commando->option('run')
            ->describedAs('Run one Worker')
            ->defaultsTo('');

        $commando->option('list')
            ->describedAs('List all workers')
            ->boolean();

        $commando->option('scan')
            ->describedAs('Scan all workers')
            ->boolean();
    }

    public function run(Commando $commando)
    {
        if ($commando['run']) {
            $this->runWorker($commando['run']);
        } else if ($commando['list']) {
            $this->listAllWorkers();
        } else if ($commando['scan']) {
            $this->scanWorkers();
        }
    }

    private function getPathWorkers() {
        return app()->get('Mini\Kernel')->getSourcePath() . '/Workers';
    }

    public function listAllWorkers() {
        $c = new \Colors\Color();

        $srcPath = $this->getPathWorkers();
        $workerScannedPath = $srcPath . '/scanned';

        if (file_exists($workerScannedPath . '/workers.php')) {

            $workers = include $workerScannedPath . '/workers.php';

            $table = new ConsoleTable();
            $table
                ->addHeader('WorkerName')
                ->addHeader('NameSpace')
                ->addHeader('Sleep Time');

            foreach ($workers as $worker) {
                $table->addRow()
                    ->addColumn($worker['workerName'])
                    ->addColumn($worker['namespace'])
                    ->addColumn($worker['sleepTime']);
            }

            $table->display();

        } else {
            echo $c('Any worker founded.')->red() . PHP_EOL;
        }

    }

    public function scanWorkers() {
        $c = new \Colors\Color();

        $workersFounded = [];
        $srcPath = $this->getPathWorkers();
        $workerScannedPath = $srcPath . '/scanned';

        if (! is_dir($srcPath)) {
            echo $c('Any worker found')->red() . PHP_EOL;
        } else {
            $workers = array_map("trim", explode("\n", shell_exec("find " . $srcPath . " -iname '*.php'")));
            $workers = array_filter($workers, function ($file) {
                if (strstr($file, ".php")) return $file;
                else return false;
            });

            $workers = array_map(function ($file) use($srcPath) {
                return str_replace($srcPath, '', $file);
            }, $workers);

            if (count ($workers) == 0) {
                echo $c('Any worker found.')->red() . PHP_EOL;
            } else {

                foreach($workers as $worker) {
                    $parser = new File($srcPath . $worker);
                    $annotations = $parser->getAnnotations();
                    foreach ($annotations as $annotation) {
                        $namespace = $annotation->getParent()->getObject()->getName();
                        $ann = $annotation->getName();
                        $workerName = $annotation->getArg();

                        if ($ann == 'worker') {
                            $obj = new $namespace;
                            $workersFounded[$workerName] = [
                                'namespace' => $namespace,
                                'workerName' => $workerName,
                                'sleepTime' => $obj->sleepTime
                            ];
                        }
                    }
                }

                if (count($workersFounded) > 0) {
                    if (! is_dir($workerScannedPath)) {
                        mkdir($workerScannedPath, 0755);
                    }
                    $workerSerialized = serialize($workersFounded);
                    $fp = fopen($workerScannedPath . '/workers.php', 'w+');
                    fwrite($fp, "<?php\n\n");
                    fwrite($fp, '$routes = \'' . $workerSerialized . '\';' . PHP_EOL . PHP_EOL);
                    fwrite($fp, 'return unserialize($routes);');
                    fclose($fp);

                    echo $c(count($workersFounded) . ' workers founded.')->green() . PHP_EOL;
                }

            }
        }
    }

    public function runWorker($name) {
        $c = new \Colors\Color();

        $srcPath = $this->getPathWorkers();
        $workerScannedPath = $srcPath . '/scanned';

        if (file_exists($workerScannedPath . '/workers.php')) {
            $workers = include $workerScannedPath . '/workers.php';
            if (isset($workers[$name])) {
                $runner = new WorkerRunner($name, $workers[$name]['namespace']);
                $runner->run();
            } else {
                echo $c('worker "' . $name . '" not found.')->red() . PHP_EOL;
            }
        } else {
            echo $c('Any worker founded.')->red() . PHP_EOL;
        }
    }
}