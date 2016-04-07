<?php

namespace Mini\Workers\Drivers;

use Pheanstalk\Pheanstalk;

class Beanstalkd
{

    private $pheanstalk;

    function __construct()
    {
        $host = env('BEANSTALKD_HOST');
        $port = env('BEANSTALKD_PORT');

        $this->pheanstalk = new Pheanstalk(sprintf(
            '%s:%s',
            $host,
            $port
        ));
    }

    private function verifyConnection() {
        if (! $this->pheanstalk->getConnection()->isServiceListening()) {
            error_log("Beanstalkd not listening");
            return false;
        }
    }

    public function addQueue($queueName, array $data) {
        $this->verifyConnection();

        $this->pheanstalk
            ->useTube($queueName)
            ->put($data);

    }

    public function getDataFromQueue($queueName) {
        $this->verifyConnection();

        $job = $this->pheanstalk
            ->watch($queueName)
            ->reserve();

        return $job->getData();
    }

    public function delete($queueName) {
        $this->verifyConnection();

        $job = $this->pheanstalk
            ->watch($queueName)
            ->reserve();

        $this->pheanstalk->delete($job);
    }
}