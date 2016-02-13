<?php

namespace Mini;

use Mini\Exceptions\MiniException;
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
        try {
            Router::setBasePath($this->basePath);
            Router::loadConfigFile('router.yaml');
            Router::matchRoutes();
        } catch (MiniException $e) {
            response()->json([
                'data' => [
                    'message' => $e->getMessage()
                ]
            ], 500);
        }

    }

}