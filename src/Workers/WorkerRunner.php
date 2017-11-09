<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 01/04/16
 * Time: 17:12
 */

namespace Mini\Workers;

use Mini\Behaviors\Cache;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class WorkerRunner extends WorkerBase
{
    /**
     * @var
     */
    private $worker;

    /**
     * @var
     */
    private $workerClass;

    /**
     * @var
     */
    private $objWorker;

    /**
     * @var
     */
    private $workerSleepTime;

    /**
     * @var null
     */
    private $lastExecution = null;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param mixed $worker
     */
    public function setWorker($worker)
    {
        $this->worker = $worker;
    }

    /**
     * @param mixed $workerClass
     */
    public function setWorkerClass($workerClass)
    {
        $this->workerClass = $workerClass;
    }


    /**
     * WorkerRunner constructor.
     * @param $worker
     * @param $workerClass
     */
    function __construct($worker, $workerClass)
    {
        $this->worker = $worker;
        $this->workerClass = $workerClass;
        $this->loop = Factory::create();

        $this->prepare();
    }

    /**
     *
     */
    public function prepare()
    {
        $this->objWorker = new $this->workerClass;
        $this->objWorker->setLoop($this->loop);
        $this->workerSleepTime = $this->objWorker->sleepTime;
    }

    /**
     * Run process
     *
     * @return void
     */
    public function run()
    {
        $this->loop->addPeriodicTimer($this->workerSleepTime / 1000, [$this, 'onInterval']);
        $this->loop->run();
    }

    /**
     * Handles an interval event
     *
     * @return void
     */
    public function onInterval()
    {
        $driver = env("WORKER_DRIVER");
        if ($driver == "BEANSTALKD") {
            $this->log("--- RUN ---");
            $queues = WorkerQueue::getDataFromQueue($this->worker);
            if (count($queues) > 0) {
                $queues = array_map('unserialize', $queues);
                foreach ($this->objWorker->removeDuplicates($queues) as $queue) {
                    $this->objWorker->run($queue);
                }
            }
            app()->get('Mini\Entity\ConnectionManager')->closeAll();
        } else if ($driver == "MEMCACHED"){
            $queueKeyLast = $this->worker . '-LAST';
            $this->log("--- RUN ---");
            $last = Cache::get($queueKeyLast);
            if ($last > $this->lastExecution) {
                $queues = WorkerQueue::getDataFromQueue($this->worker);
                if (count($queues) > 0) {
                    foreach ($queues as $queue) {
                        $obj = Cache::get($this->worker . '-' . $queue);
                        $this->objWorker->run(unserialize($obj));
                        WorkerQueue::delete($this->worker . '-' . $queue);
                    }
                }
            }
            app()->get('Mini\Entity\ConnectionManager')->closeAll();
        }
    }
}
