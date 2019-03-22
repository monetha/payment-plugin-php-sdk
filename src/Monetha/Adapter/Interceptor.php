<?php

namespace Monetha\Adapter;

interface Interceptor {
    public function getPrice();

    public function getName();

    public function getQtyOrdered();
}
