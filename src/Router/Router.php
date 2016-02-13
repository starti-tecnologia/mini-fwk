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
                include $basePath . '/src/Controllers/' . $className . '.php';
            });
        }

        if (self::$parsedFile != null) {
            if (isset(self::$parsedFile['routes'])) {
                foreach (self::$parsedFile['routes'] as $route) {
                    $route_uri = $route[0];
                    $route_controller = $route[1];
                    $route_method = $route[2];
                    if ($route_uri == $request_uri && $method == $route_method) {
                        self::loadClass($route_controller);
                    }
                }
            }
        }
    }



    private static function loadClass($route_controller) {
        list($controller, $method) = explode(".", $route_controller);

        $obj = new $controller;
        $obj->{$method}();
    }

}