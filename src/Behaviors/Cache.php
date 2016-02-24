<?php

namespace Mini\Behaviors;

use Mini\Exceptions\MiniException;

class Cache
{

    /**
     * @return \Memcached|null
     * @throws MiniException
     */
    private static function instance() {
        return app()->get('Memcached');
    }

    public static function get($key) {
        return self::instance()->get($key);
    }

    public static function set($key, $value) {
        return self::instance()->set($key, $value);
    }

    public static function remove($key) {
        return self::instance()->delete($key);
    }

}