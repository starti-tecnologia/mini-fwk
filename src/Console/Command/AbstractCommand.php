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

    public function __construct()
    {

    }

    public function setKernel(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function confirm($message = 'Are you sure you want to do this', $yes = 'fwk-yes', $no = 'n')
    {
        $c = new \Colors\Color();
        $message = $message . ' ['. $yes .'/' . $no . '] ';

        print $c($message)->bold();

        flush();

        $confirmation  =  trim(fgets(STDIN));

        if ($confirmation !== $yes) {
            print $c('Aborted.')->yellow() . PHP_EOL;
            exit (0);
        }
    }

    public function write($message, $color = 'white')
    {
        $c = new \Colors\Color();
        $output = $c($message);
        $output = $output->$color();
        echo $output . PHP_EOL;
    }
}
