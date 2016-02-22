<?php

namespace Mini\Entity;

use Dotenv\Dotenv;
use Mini\Exceptions\MiniException;

class ConnectionManager
{
    /**
     * @var array
     */
    private $connections;

    /**
     * @var \Mini\Kernel
     */
    private $kernel;

    public function __construct()
    {
        $this->kernel = app()->get('Mini\Kernel');
        $this->connections = [];
    }

    /**
     * @param $name
     * @return Connection
     * @throws MiniException
     */
    public function getConnection($name)
    {
        if (! isset($this->connections[$name])) {
            $databases = $this->kernel->getConfigSection('db');

            if (! isset($databases[$name])) {
                throw new MiniException('Database ' . $name . ' is not configured');
            }

            $this->connections[$name] = new Connection($databases[$name], $name);
        }

        return $this->connections[$name];
    }
}
