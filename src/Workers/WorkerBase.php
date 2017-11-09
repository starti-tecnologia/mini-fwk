<?php

namespace Mini\Workers;

use Colors\Color;
use React\EventLoop\LoopInterface;

class WorkerBase
{
    /**
     * @var LoopInterface
     */
    private $loop;

    public function log($msg)
    {
        $c = new Color();
        $c->setTheme(
            array(
                'date' => array('white', 'bg_blue'),
                'error' => 'red',
            )
        );

        $date = date('d/m/Y H:i:s');
        $text = <<<EOF
<date>{$date}</date> {$msg}
EOF;
        echo $c($text)->colorize() . PHP_EOL;
    }

    public function removeDuplicates(array $queues)
    {
        return $queues;
    }

    /**
     * Set execution loop
     *
     * @param LoopInterface $loop
     * @return void
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Get execution loop
     *
     * @return LoopInterface
     */
    protected function getLoop()
    {
        return $this->loop;
    }
}
