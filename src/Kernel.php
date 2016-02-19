<?php

namespace Mini;

use Mini\Exceptions\MiniException;
use Mini\Router\Router;
use Mini\Entity\Model;
use ErrorException;

class Kernel
{
    /**
     * @var
     */
    private $basePath;

    /**
     * Kernel constructor.
     */
    public function __construct($config)
    {
        $this->basePath = isset($config['basePath']) ? $config['basePath'] : realpath(dirname($_SERVER['DOCUMENT_ROOT']));
        include_once dirname(__FILE__) . '/Helpers/Instance/helpers.php';
        set_error_handler([$this, 'handleError']);

        $this->setUpContainer();
    }

    public function setUpContainer()
    {
        $container = app();
        $container->register('Mini\Kernel', $this);
        $container->register('Mini\Entity\Model', function () {
            return new Model();
        });
    }

    /**
     * Starts the request routing
     */
    public function bootstrap()
    {
        $this->addRouting();
    }

    /**
     * Adding routing config file
     */
    public function addRouting()
    {
        try {
            Router::setBasePath($this->basePath);
            Router::loadConfigFile('routes.php');
            Router::matchRoutes();
        } catch (MiniException $e) {
            response()->json([
                'data' => [
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
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

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getMigrationsPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'migrations';
    }

    public function getEntitiesPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Models';
    }
}
