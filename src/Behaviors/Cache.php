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

    public static function set($key, $value, $expiration = null) {
        if ($expiration !== null && $expiration > 0) {
            $expiration = time() + ($expiration * 60);
        }
        return self::instance()->set($key, $value, $expiration);
    }

    public static function delete($key) {
        return self::instance()->delete($key);
    }

}