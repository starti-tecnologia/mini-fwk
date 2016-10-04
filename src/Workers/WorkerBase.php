<?php

namespace Mini\Workers;
use Colors\Color;

class WorkerBase
{
    public function log($msg) {
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
}
