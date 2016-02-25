<?php

namespace Mini;

use Mini\Exceptions\MiniException;
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

        $this->setUpContainer();
        $this->setUpConfiguration();
    }

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

    public function setUpConfiguration()
    {
        Router::setBasePath($this->basePath);
        Router::loadConfigFile('routes.php');
        $this->setUpCache();

        $this->application->afterConfigurationSetup();
    }

    private function setUpCache() {
        if (env('MEMCACHED_HOST') !== null && env('MEMCACHED_PORT') !== null) {
            $cacheInstance = new \Memcached();
            $cacheInstance->addServer(env('MEMCACHED_HOST'), env('MEMCACHED_PORT'));
            app()->register('Memcached', $cacheInstance);
        }
    }

    /**
     * Starts the request routing
     */
    public function bootstrap()
    {
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
     * Handle a Exception
     *
     * @param  Exception $exception
     * @return void
     */
    public function handleException(\Throwable $exception)
    {
        if (defined('IS_CONSOLE')) throw $exception;

        $this->application->onException($exception);
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getMigrationsPath()
    {
        return $this->basePath . '/migrations';
    }

    public function getEntitiesPath()
    {
        return $this->basePath . '/src/Models';
    }

    public function getConfigSection($section)
    {
        if (! isset($this->config[$section])) {
            $this->config[$section] = require $this->basePath . '/config/'  . escapeshellcmd($section) . '.php';
        }

        return $this->config[$section];
    }

    public function getControllersPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controllers';
    }

    public function getRouterPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'routers';
    }
}
