<?php

namespace Monetha\Adapter;

interface InterceptorInterface {
    public function getPrice();

    public function getName();

    public function getQtyOrdered();
}
