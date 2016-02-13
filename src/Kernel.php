<?php

namespace Mini;

use Mini\Router\Router;

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
     * Adding routing config file
     */
    public function addRouting() {

        Router::setBasePath($this->basePath);
        Router::loadConfigFile('router.yaml');

    }

}