<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 01/04/16
 * Time: 17:25
 */

namespace Mini\Workers;


use Mini\Behaviors\Cache;
use Mini\Workers\Drivers\Beanstalkd;
use Mini\Workers\Drivers\Memcached;

class WorkerQueue
{
    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @return Beanstalkd|Memcached|null
     */
    private static function instance()
    {
        if (self::$instance === null) {

            $driver = env('WORKER_DRIVER');
            if ($driver == 'MEMCACHED')
                self::$instance = new Memcached;
            else if ($driver == 'BEANSTALKD')
                self::$instance = new Beanstalkd;
        }

        return self::$instance;
    }


    /**
     * @param $queueName
     * @param array $data
     * @return null
     */
    public static function addQueue($queueName, array $data) {
        if (self::instance() === null) return null;

        self::$instance->addQueue($queueName, $data);
    }

    /**
     * @param $queueName
     * @return null
     */
    public static function getDataFromQueue($queueName) {
        if (self::instance() === null) return null;

        return self::$instance->getDataFromQueue($queueName);
    }

    public static function delete($queueName) {
        if (self::instance() === null) return null;

        self::$instance->delete($queueName);
    }

}