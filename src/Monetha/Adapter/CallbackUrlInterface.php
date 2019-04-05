<?php

namespace Monetha\Adapter;

interface CallbackUrlInterface {
    /**
     * Monetha Gateway will send JSON payload about the deal and occurred event on that URL.
     *
     * @return string
     */
    public function getCallbackUrl();
}
