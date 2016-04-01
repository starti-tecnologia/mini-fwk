<?php

namespace Mini\Workers;

class Worker extends WorkerBase
{

    public $sleepTime = 1000;

    public function run($queue = null) {
        $this->log("Init worker");
    }

}