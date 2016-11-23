<?php

namespace Mini\Console\Command;

use Mini\Console\Common\ConsoleTable;
use Mini\Router\Router;
use Commando\Command as Commando;
use Notoj\File;

class RouteScanCommand extends AbstractCommand
{
    private $methods = [
        'get', 'post', 'delete', 'put'
    ];

    private $helpers = [
        'middleware'
    ];

    /**
     * @return string
     */
    public function getName()
    {
        return 'route:scan';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Re-scan router file';
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
        $kernel = app()->get('Mini\Kernel');
        $path = $kernel->getControllersPath();
        $pathRouter = $kernel->getRouterPath();

        //$controllers = scandir($path);
        $controllers = array_map("trim", explode("\n", shell_exec("find " . $path . " -iname '*.php'")));
        $controllers = array_map(function ($file) use($path) {
            return str_replace($path, '', $file);
        }, $controllers);


        $controllers = array_filter($controllers, function ($file) {
            if (strstr($file, ".php")) return $file;
            else return false;

        });

        $routesScanned = [];
        $routesScannedFns = [];

        foreach ($controllers as $controller) {
            $parser = new File($path . DIRECTORY_SEPARATOR . $controller);
            $annotations = $parser->getAnnotations();
            foreach ($annotations as $annotation) {
                $namespace = $annotation->getParent()->getObject()->getObject()->class->getName();
                $ann = $annotation->getName();
                $fn = $annotation->getObjectName();

                $match = $this->match($ann);
                if ($match == 'method') {
                    $route = $annotation->getArg();
                    $length = '';
                    if (isset($routesScannedFns[$fn])) {
                        $length = '-' . $routesScannedFns[$fn];
                        $routesScannedFns[$fn]++;
                    } else {
                        $routesScannedFns[$fn] = 1;
                    }

                    $routesScanned[$fn . $length] = [
                        'route' => $route,
                        'uses' => sprintf(
                            '%s@%s',
                            $namespace,
                            $fn
                        ),
                        'method' => strtoupper($ann)
                    ];
                } else if ($match == 'middleware') {
                    $middleware = $annotation->getArg();
                    $routesScanned[$fn . $length]['middleware'][] = $middleware;
                }
            }
        }

        if (count($routesScanned) > 0) {
            $routesSerialized = serialize($routesScanned);
            $fp = fopen($pathRouter . DIRECTORY_SEPARATOR . "/routes.scanned.php", "w+");
            fwrite($fp, "<?php\n\n");
            fwrite($fp, '$routes = \'' . $routesSerialized . '\';' . PHP_EOL . PHP_EOL);
            fwrite($fp, 'return unserialize($routes);');
            fclose($fp);
        }

        $table = new ConsoleTable();
        $table
            ->addHeader('Method')
            ->addHeader('Uri')
            ->addHeader('Controller')
            ->addHeader('Middlewares');

        foreach ($routesScanned as $route) {
            $table->addRow()
                ->addColumn($route['method'])
                ->addColumn($route['route'])
                ->addColumn($route['uses'])
                ->addColumn(
                    implode(
                        ', ',
                        isset($route['middleware']) ? $route['middleware'] : []
                    )
                );
        }

        $table->display();
    }

    /**
     * @param $string
     * @return string
     */
    private function match($string) {
        if ($this->matchMethod($string)) {
            return 'method';
        } else {
            return $this->matchHelpers($string);
        }
    }

    /**
     * @param $string
     * @return bool
     */
    private function matchMethod($string) {
        foreach ($this->methods as $method) {
            $pttrn = '/' . $method . '/';
            if (preg_match($pttrn, $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $string
     * @return string
     */
    private function matchHelpers($string) {
        foreach ($this->helpers as $helper) {
            $pttrn = '/' . $helper . '/';
            if (preg_match($pttrn, $string)) {
                return $helper;
            }
        }
        return '';
    }

}
