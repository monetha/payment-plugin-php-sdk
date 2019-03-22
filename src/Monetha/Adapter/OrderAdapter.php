<?php

namespace Monetha\Adapter;

use Monetha\Adapter\Interceptor;
use Monetha\Adapter\InterceptorAdapter;

class OrderAdapter implements OrderAdapterInterface {
    /**
     * @var \Cart
     */
    private $cart;

    /**
     * @var Interceptor[]
     */
    private $items = [];

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(\Cart $cart, $currencyCode, $baseUrl) {
        $this->cart = $cart;
        $this->currencyCode = $currencyCode;
        $this->baseUrl = $baseUrl;

        $items = $this->cart->getProducts();
        foreach ($items as $item) {
            $this->items[] = new InterceptorAdapter($item);
        }
    }

    /**
     * @return Interceptor[]
     */
    public function getItems() {
        return $this->items;
    }

    public function getGrandTotalAmount() {
        return $this->cart->getOrderTotal();
    }

    public function getCurrencyCode() {
        return $this->currencyCode;
    }

    public function getBaseUrl() {
        return $this->baseUrl;
    }

    /**
     * @return mixed
     */
    public function getCartId()
    {
        return $this->cart->id;
    }
}
