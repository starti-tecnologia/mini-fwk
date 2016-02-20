<?php

namespace Mini;

use Mini\Exceptions\MiniException;
use Mini\Router\Router;
use Mini\Entity\Model;

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

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getMigrationsPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'migrations';
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
