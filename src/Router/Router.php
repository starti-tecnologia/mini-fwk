<?php

namespace Mini\Router;

use Mini\Exceptions\MiniException;

/**
 * Class Router
 * @package Mini\Router
 */
class Router
{
    /**
     * @var
     */
    private static $basePath;

    /**
     * @var null
     */
    private static $parsedFile = null;

    /**
     * @var null
     */
    private static $middleware = null;

    /**
     * @var bool
     */
    private static $onloadControllers = false;

    /**
     * @var null
     */
    private static $proxyInstance = null;

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

    /**
     * @param null $middleware
     */
    public static function setMiddleware($middleware)
    {
        self::$middleware = $middleware;
    }

    /**
     * @return \Mini\Proxy\RestProxy
     */
    private static function proxy() {
        if (self::$proxyInstance === null) {
            self::$proxyInstance = app()->get('Mini\Kernel')->proxy;
        }
        return self::$proxyInstance;
    }


    /**
     * @param $config
     * @throws MiniException
     * @throws \Exception
     */
    public static function loadConfigFile($config) {
        $basePathRouter = self::$basePath . '/src/routers/';
        $routeFile = $basePathRouter . $config;

        if (file_exists($basePathRouter . 'routes.scanned.php'))
            $routeFile = $basePathRouter . 'routes.scanned.php';

        if (!file_exists($routeFile)) {
            throw new \Exception("Route config file not found.");
        }

        $routes = include_once $routeFile;

        if (!empty($routes)) {
            self::setParsedFile($routes);
            self::loadMiddlewareFile();
        } elseif (!defined('IS_CONSOLE')) {
            throw new MiniException("Routes variable not found.");
        }
    }

    /**
     *
     */
    private static function loadMiddlewareFile() {
        $middlewareFile = self::$basePath . '/src/routers/middlewares.php';

        if (file_exists($middlewareFile)) {
            $middlewares = include_once $middlewareFile;

            if (!empty($middlewares))
                self::setMiddleware($middlewares);
        }
    }

    /**
     * @param $routeMethod
     * @return bool
     */
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

    /**
     * @throws MiniException
     */
    public static function matchRoutes() {
        $urlParts = parse_url($_SERVER['REQUEST_URI']);
        $request_uri = $urlParts['path'];

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
                    $route_middlewares = isset($route['middleware']) ? $route['middleware'] : [];

                    $pattern = "@^" . preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($route_uri)) . "$@D";
                    $matches = Array();
                    // check if the current request matches the expression
                    if(self::matchMethod($route_method) && preg_match($pattern, $request_uri, $matches)) {
                        app()->get('Mini\Kernel')->setRequestRouting($route_uri);
                        // remove the first match
                        array_shift($matches);
                        // call the callback with the matched positions as params
                        self::loadClass($route_controller, $route_middlewares, $matches);
                        $routeFound = true;
                    }
                }
                self::proxy()->onAfterRequest();

                if (!$routeFound)
                    self::proxy()->onRouterError();
                    //throw new MiniException("Route not found.");
            }
        }
    }

    /**
     * @param $route_controller
     * @param $route_middlewares
     * @param $params
     * @throws MiniException
     */
    private static function loadClass($route_controller, $route_middlewares, $params) {
        self::proxy()->onBeforeRequest();


        list($controller, $method) = explode("@", $route_controller);

        if (count($route_middlewares) > 0) {
            foreach ($route_middlewares as $string) {
                if (strstr($string, ":")) list($middleware, $value) = explode(":", $string);
                else {
                    $middleware = $string;
                    $value = null;
                }

                if (isset(self::$middleware[$middleware])) {
                    $midClass = self::$middleware[$middleware];
                    $midObj = new $midClass;

                    if (method_exists($midObj, 'handler')) {
                        call_user_func_array(array($midObj, 'handler'), [$value]);
                    } else {
                        throw new MiniException(sprintf(
                            "Handler Method not found on middleware (%s)",
                            $middleware
                        ));
                    }
                }
            }
        }

        $obj = new $controller;
        if (count($params) > 0) {
            call_user_func_array(array($obj, $method), $params);

        } else
            $obj->{$method}();
    }

    /**
     * @return array
     */
    public static function listRoutes()
    {
        $routes = [];

        if (self::$parsedFile != null) {
            if (count(self::$parsedFile) > 0) {
                foreach (self::$parsedFile as $routeName => $route) {
                    $routes[] = [
                        'uri' => $route['route'],
                        'controller' => $route['uses'],
                        'method' => $route['method'],
                        'middlewares' => isset($route['middleware']) ? $route['middleware'] : []
                    ];
                }
            }
        }

        return $routes;
    }
}
