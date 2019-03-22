<?php

namespace Monetha\Adapter;

interface OrderAdapterInterface {
    public function getItems();

    public function getGrandTotalAmount();

    public function getCurrencyCode();

    public function getBaseUrl();

    public function getCartId();
}
