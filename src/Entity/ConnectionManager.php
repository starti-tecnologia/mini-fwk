<?php

namespace Mini\Entity;

use Dotenv\Dotenv;
use Mini\Exceptions\MiniException;
use Mini\Entity\Mongo\Connection as MongoConnection;

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

            if ($databases[$name]['driver'] == "mongodb") {
                $this->connections[$name] = new MongoConnection($databases[$name], $name);
            } else {
                $this->connections[$name] = new Connection($databases[$name], $name);
            }
        }

        return $this->connections[$name];
    }

    public function closeAll()
    {
        $this->connections = [];
    }
}
