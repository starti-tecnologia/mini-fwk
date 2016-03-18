<?php

namespace Mini\Proxy;

use Mini\Exceptions\MiniException;

class RestProxy
{

    /**
     *
     */
    public function onBeforeRequest() {

    }

    /**
     *
     */
    public function onAfterRequest() {

    }

    /**
     * @throws MiniException
     */
    public function onRouterError() {
        throw new MiniException("Route not found.");
    }

}