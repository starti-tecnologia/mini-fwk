<?php

namespace Mini;

use Mini\Exceptions\MiniException;

class Container
{
    /**
     * @var Container
     */
    private static $instance;

    /**
     * @var array
     */
    private $registry;

    private function __construct()
    {
        $this->registry = [];
    }

    /**
     * Give acess to singleton
     */
    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register a instance or a factory function into the container
     * 
     * @var $name Identification name, example: 'kernel'
     * @var $instanceorFunction Object instance or factory function that receives the container as first argument
     */
    public function register($name, $instanceOrFunction)
    {
        $this->registry[$name] = $instanceOrFunction;
    }

    /**
     * Get a instance from the container. If the instance is a factory function,
     * it is created only one time and returned on subsequent calls
     */
    public function get($name)
    {
        $instanceOrFunction = isset($this->registry[$name]) ? $this->registry[$name] : null;

        if (! $instanceOrFunction) {
            throw new \Exception('Instance of ' . $name . ' not found');
        }

        if (! is_callable($instanceOrFunction)) {
            return $instanceOrFunction;
        } else {
            $this->registry[$name] = call_user_func_array($instanceOrFunction, [$this]);
            return $this->registry[$name];
        }
    }
}
