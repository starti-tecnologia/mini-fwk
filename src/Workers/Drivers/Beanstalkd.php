<?php

namespace Mini\Workers\Drivers;

use Pheanstalk\Pheanstalk;

class Beanstalkd
{

    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * Beanstalkd constructor.
     */
    function __construct()
    {
        $host = env('BEANSTALKD_HOST');
        $port = env('BEANSTALKD_PORT');

        $this->pheanstalk = new Pheanstalk($host, $port);

    }

    /**
     * @return bool
     */
    private function verifyConnection() {
        if (! $this->pheanstalk->getConnection()->isServiceListening()) {
            error_log("Beanstalkd not listening");
            return false;
        }
    }

    private function checkTube($tube) {
        foreach ($this->pheanstalk->listTubes() as $listTube) {
            if ($listTube == $tube) return true;
        }
        return false;
    }

    /**
     * @param $key
     * @return bool|\Pheanstalk\Job|string
     */
    private function get($key) {
        if ($this->checkTube($key)) {

            $statsTube = $this->pheanstalk->statsTube($key);
            $readyJobs = (int) $statsTube['current-jobs-ready'];

            $jobs = [];
            for ($i = 0; $i < $readyJobs; ++$i) {
                $job = $this->pheanstalk->peekReady($key);
                $jobs[] = $job->getData();
                $this->pheanstalk->delete($job);
            }

            return $jobs;

        } else return null;
    }

    /**
     * @param $key
     * @param $data
     */
    private function set($key, $data) {
        if (!$this->checkTube($key)) {
            $this->pheanstalk
                ->useTube($key)
                ->put($data);
        } else {
            $this->pheanstalk->putInTube($key, $data);
        }
    }

    /**
     * @param $queueName
     * @param array $data
     */
    public function addQueue($queueName, array $data) {
        $this->verifyConnection();

        $this->set($queueName, serialize($data));
    }

    /**
     * @param $queueName
     * @return array|mixed
     */
    public function getDataFromQueue($queueName) {
        $this->verifyConnection();

        $jobData = $this->get($queueName);

        if ($jobData !== null) {
            return $jobData;
        }
    }

    /**
     * @param $queueName
     */
    public function delete($queueName) {
        $this->verifyConnection();

        $job = $this->pheanstalk
            ->watch($queueName)
            ->reserve();

        $this->pheanstalk->delete($job);
    }
}