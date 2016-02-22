<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Controllers\BaseController;
use Notoj\File;
use Notoj\Notoj;
use Notoj\ReflectionClass;


class RouteScanCommand extends AbstractCommand
{
    /**
     *
     */
    const METHODS = [
        'get', 'post', 'delete', 'put'
    ];
    /**
     *
     */
    const HELPERS = [
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
                    if (isset($routesScanned[$fn]))
                        $length = count($routesScanned[$fn]);

                    $routesScanned[$fn . '-' . $length] = [
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
                    $routesScanned[$fn]['middleware'][] = $middleware;
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
        foreach (self::METHODS as $method) {
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
        foreach (self::HELPERS as $helper) {
            $pttrn = '/' . $helper . '/';
            if (preg_match($pttrn, $string)) {
                return $helper;
            }
        }
        return '';
    }

}