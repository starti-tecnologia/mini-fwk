<?php

namespace Mini\Router;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;

class Router
{
    private static $basePath;

    private static $parsedFile = null;

    private static $onloadControllers = false;
    /**
     * @param mixed $basePath
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }

    /**
     * @param mixed $parsedFile
     */
    public static function setParsedFile($parsedFile)
    {
        self::$parsedFile = $parsedFile;
    }

    /**
     * @param boolean $onloadControllers
     */
    public static function setOnloadControllers($onloadControllers)
    {
        self::$onloadControllers = $onloadControllers;
    }


    public static function loadConfigFile($config) {
        $yamlFile = self::$basePath . '/src/routers/' . $config;


        if (!file_exists($yamlFile)) {
            throw new \Exception("Yaml config file not found.");
        }

        $yaml = new Parser();
        try {
            $parsed = $yaml->parse(file_get_contents($yamlFile));
            self::setParsedFile($parsed);

        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
    }

    public static function matchRoutes() {
        $method = $_SERVER['REQUEST_METHOD'];
        $request_uri = $_SERVER['REQUEST_URI'];

        if (self::$onloadControllers) {
            $basePath = self::$basePath;
            spl_autoload_register(function ($className) use ($basePath) {
                $file = $basePath . '/src/Controllers/' . $className . '.php';
                if (file_exists($file)) include $file;
            });
        }

        if (self::$parsedFile != null) {
            if (isset(self::$parsedFile['routes'])) {
                foreach (self::$parsedFile['routes'] as $route) {
                    $route_uri = $route[0];
                    $route_controller = $route[1];
                    $route_method = $route[2];

                    $pattern = "@^" . preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($route_uri)) . "$@D";
                    $matches = Array();
                    // check if the current request matches the expression
                    if($method == $route_method && preg_match($pattern, $request_uri, $matches)) {
                        // remove the first match
                        array_shift($matches);
                        // call the callback with the matched positions as params
                        self::loadClass($route_controller, $matches);

                    }
                }
            }
        }
    }



    private static function loadClass($route_controller, $params) {
        list($controller, $method) = explode(".", $route_controller);

        $obj = new $controller;
        if (count($params) > 0) {
            //$obj->{$method}();
            call_user_func_array(array($obj, $method), $params);

        } else
            $obj->{$method}();
    }

}