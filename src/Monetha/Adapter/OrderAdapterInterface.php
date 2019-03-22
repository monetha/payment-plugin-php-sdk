<?php

namespace Monetha\Adapter;

interface OrderAdapterInterface {
    /**
     * @return Interceptor[]
     */
    public function getItems();

    public function getGrandTotalAmount();

    public function getCurrencyCode();

    public function getBaseUrl();

    public function getCartId();
}
