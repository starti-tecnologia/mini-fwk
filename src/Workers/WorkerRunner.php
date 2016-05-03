<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 01/04/16
 * Time: 17:12
 */

namespace Mini\Workers;


use Mini\Behaviors\Cache;

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

        $this->prepare();
    }

    /**
     *
     */
    public function prepare() {
        $this->objWorker = new $this->workerClass;
        $this->workerSleepTime = $this->objWorker->sleepTime;
    }

    /**
     *
     */
    public function run() {

        $driver = env("WORKER_DRIVER");
        if ($driver == "BEANSTALKD") {

            while (1) {
                $this->log("--- RUN ---");
                $queues = WorkerQueue::getDataFromQueue($this->worker);
                if (count($queues) > 0) {
                    foreach ($queues as $queue) {
                        $this->objWorker->run(unserialize($queue));
                    }
                }
                app()->get('Mini\Entity\ConnectionManager')->closeAll();
                sleep($this->workerSleepTime / 1000);
            }
        } else if ($driver == "MEMCACHED"){
            $queueKeyLast = $this->worker . '-LAST';
            while (1) {
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
                sleep($this->workerSleepTime / 1000);
            }
        }

    }

}