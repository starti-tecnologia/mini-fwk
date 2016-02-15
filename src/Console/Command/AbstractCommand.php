<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Kernel;

abstract class AbstractCommand
{
    /**
     * @var Kernel
     */
    protected $kernel;

    public abstract function getName();
    public abstract function getDescription();
    public abstract function setUp(Commando $commando);
    public abstract function run(Commando $commando);

    public function setKernel(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }
}
