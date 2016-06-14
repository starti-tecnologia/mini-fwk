<?php

namespace Mini;

use Mini\Proxy\RestProxy;
use Mini\Router\Router;
use Mini\Entity\ConnectionManager;
use Mini\Validation\Validator;
use ErrorException;

class Kernel
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var
     */
    private $requestRouting;

    /**
     * Kernel constructor.
     */
    public function __construct($config)
    {
        include_once dirname(__FILE__) . '/Helpers/Instance/helpers.php';
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);

        $this->config = empty($config) ? [] : $config;
        $this->basePath = isset($this->config['basePath']) ? $this->config['basePath'] : realpath(dirname($_SERVER['DOCUMENT_ROOT']));
        $this->application = isset($this->config['application']) ? $this->config['application'] : new Application;
        $this->proxy = isset($this->config['proxy']) ? $this->config['proxy'] : new RestProxy;

        $this->setUpContainer();
        $this->setUpConfiguration();
    }

    /**
     *
     */
    public function setUpContainer()
    {
        $container = app();
        $container->register('Mini\Kernel', $this);
        $container->register('Mini\Entity\ConnectionManager', function () {
            return new ConnectionManager();
        });
        $container->register('Mini\Validation\Validator', function () {
            return new Validator();
        });
        $this->application->afterContainerSetup();
    }

    /**
     *
     */
    public function setUpConfiguration()
    {
        $this->setUpCache();

        $this->application->afterConfigurationSetup();
    }

    /**
     *
     */
    private function setUpCache() {
        if (env('MEMCACHED_HOST') !== null && env('MEMCACHED_PORT') !== null) {
            $cacheInstance = new \Memcached();
            $cacheInstance->addServer(env('MEMCACHED_HOST'), env('MEMCACHED_PORT'));
            app()->register('Memcached', $cacheInstance);
        }
    }

    /**
     *
     */
    public function loadConfiguration()
    {
        Router::setBasePath($this->basePath);
        Router::loadConfigFile('routes.php');
    }

    /**
     * Starts the request routing
     */
    public function bootstrap()
    {
        $this->loadConfiguration();
        Router::matchRoutes();
    }

    /**
     * Convert a PHP error to an ErrorException.
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }


    /**
     * @param $exception
     */
    public function handleException($exception)
    {
        if (defined('IS_CONSOLE')) throw $exception;

        $this->application->onException($exception);
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getMigrationsPath()
    {
        return $this->basePath . '/migrations';
    }

    /**
     * @return string
     */
    public function getEntitiesPath()
    {
        return $this->basePath . '/src/Models';
    }

    /**
     * @param $section
     * @return mixed
     */
    public function getConfigSection($section)
    {
        if (! isset($this->config[$section])) {
            $this->config[$section] = require $this->basePath . '/config/'  . escapeshellcmd($section) . '.php';
        }

        return $this->config[$section];
    }

    /**
     * @return string
     */
    public function getSourcePath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'src';
    }

    /**
     * @return string
     */
    public function getControllersPath()
    {
        return $this->getSourcePath() . DIRECTORY_SEPARATOR . 'Controllers';
    }

    /**
     * @return string
     */
    public function getCommandsPath()
    {
        return $this->getSourcePath() . DIRECTORY_SEPARATOR . 'Commands';
    }

    /**
     * @return string
     */
    public function getRouterPath()
    {
        return $this->getSourcePath() . DIRECTORY_SEPARATOR . 'routers';
    }

    /**
     * @return string
     */
    public function getSeedsPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'seeds';
    }

    /**
     * @param $route
     */
    public function setRequestRouting($route) {
        $this->requestRouting = $route;
    }

    /**
     * @return mixed
     */
    public function getRequestRouting() {
        return $this->requestRouting;
    }

}
