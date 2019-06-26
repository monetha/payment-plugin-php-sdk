<?php

namespace Monetha\Adapter;

interface ReturnUrlUrlInterface {
    /**
     * Monetha Gateway will show a link to that URL after payment succeeded.
     *
     * @return string
     */
    public function getReturnUrl();
}
