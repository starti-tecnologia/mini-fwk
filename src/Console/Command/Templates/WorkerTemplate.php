<?php

namespace App\Workers;

use Mini\Workers\Worker;

/**
 * @package App\Workers
 * @Worker("WorkerNamePlaceholder")
 */
class ClassNamePlaceholder extends Worker
{
    public $sleepTime = SleepTimePlaceholder;

    public function run($queue = null)
    {
        parent::run($queue);

        // this method is auto-generated, please modify it to your needs
    }
}
