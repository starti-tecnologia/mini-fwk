<?php

namespace Mini\Router;

use Mini\Exceptions\MiniException;

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
        $routeFile = self::$basePath . '/src/routers/' . $config;

        if (!file_exists($routeFile)) {
            throw new \Exception("Route config file not found.");
        }

        include_once $routeFile;

        if (isset($routes))
            self::setParsedFile($routes);
        else
            throw new MiniException("Routes variable not found.");
    }

    private static function matchMethod($routeMethod) {
        $method = $_SERVER['REQUEST_METHOD'];

        if (preg_match("/|/", $routeMethod)) {
            if (preg_match("/" . $method . "/", $routeMethod)) {
                return true;
            } else {
                return false;
            }
        } else if ($method == $routeMethod) {
            return true;
        } else {
            return false;
        }

    }

    public static function matchRoutes() {
        $request_uri = $_SERVER['REQUEST_URI'];

        if (self::$onloadControllers) {
            $basePath = self::$basePath;
            spl_autoload_register(function ($className) use ($basePath) {
                $file = $basePath . '/src/Controllers/' . $className . '.php';
                if (file_exists($file)) include $file;
            });
        }

        $routeFound = false;
        if (self::$parsedFile != null) {
            if (count(self::$parsedFile) > 0) {
                foreach (self::$parsedFile as $routeName => $route) {
                    $route_uri = $route['route'];
                    $route_controller = $route['uses'];
                    $route_method = $route['method'];

                    $pattern = "@^" . preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($route_uri)) . "$@D";
                    $matches = Array();
                    // check if the current request matches the expression
                    if(self::matchMethod($route_method) && preg_match($pattern, $request_uri, $matches)) {
                        // remove the first match
                        array_shift($matches);
                        // call the callback with the matched positions as params
                        self::loadClass($route_controller, $matches);
                        $routeFound = true;
                    }
                }

                if (!$routeFound)
                    throw new MiniException("Route not found.");
            }
        }
    }



    private static function loadClass($route_controller, $params) {
        list($controller, $method) = explode("@", $route_controller);

        $obj = new $controller;
        if (count($params) > 0) {
            call_user_func_array(array($obj, $method), $params);

        } else
            $obj->{$method}();
    }

}