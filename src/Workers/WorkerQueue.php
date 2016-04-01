<?php
/**
 * Created by PhpStorm.
 * User: jonathas
 * Date: 01/04/16
 * Time: 17:25
 */

namespace Mini\Workers;


use Mini\Behaviors\Cache;

class WorkerQueue
{

    public static function addQueue($queueName, array $data) {
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

    public static function getDataForQueue($queueName) {
        $keyLast = $queueName . '-LAST';
        $keyQueueObj = $queueName . '-OBJ';
        $allObj = Cache::get($keyQueueObj);
        Cache::delete($keyQueueObj);
        Cache::delete($keyLast);

        if ($allObj)
            return unserialize($allObj);
        else return [];
    }

}