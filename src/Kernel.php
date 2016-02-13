<?php

namespace Mini;

use PHPRouter\RouteCollection;
use PHPRouter\Config;
use PHPRouter\Router;
use PHPRouter\Route;

class Kernel
{
    /**
     * @var
     */
    private $basePath;

    /**
     * Kernel constructor.
     */
    public function __construct()
    {
        $this->basePath = realpath(dirname($_SERVER['DOCUMENT_ROOT']));

        include_once dirname(__FILE__) . '/Helpers/Instance/helpers.php';
        
        $this->addRouting();
    }

    /**
     *
     */
    public function addRouting() {
        $config = Config::loadFromFile($this->basePath . '/src/routers/router.yaml');
        $router = Router::parseConfig($config);
        $router->matchCurrentRequest();
    }

}