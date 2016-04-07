<?php

namespace Mini\Workers\Drivers;

use Mini\Behaviors\Cache;

class Memcached
{

    public function addQueue($queueName, array $data) {
        $microtime = microtime(true);
        $key = $queueName . '-' . $microtime;
        Cache::set($key, serialize($data));

        $keyStatus = $queueName . '-LAST';
        Cache::set($keyStatus, $microtime);

        $keyQueueObj = $queueName . '-OBJ';
        $allObj = Cache::get($keyQueueObj);
        if (! $allObj) $allObj = [];
        else $allObj = unserialize($allObj);

        $allObj[] = $microtime;
        Cache::set($keyQueueObj, serialize($allObj));
    }

    public function getDataFromQueue($queueName) {
        $keyLast = $queueName . '-LAST';
        $keyQueueObj = $queueName . '-OBJ';
        $allObj = Cache::get($keyQueueObj);
        Cache::delete($keyQueueObj);
        Cache::delete($keyLast);

        if ($allObj)
            return unserialize($allObj);
        else return [];
    }

    public function delete($queueName) {
        Cache::delete($queueName);
    }

}