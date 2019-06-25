<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:52
 */

namespace Monetha\Payload;


use Monetha\Adapter\CallbackUrlInterface;
use Monetha\Adapter\ReturnUrlUrlInterface;
use Monetha\Adapter\OrderAdapterInterface;

class CreateOffer extends AbstractPayload
{
    const LINE_ITEMS_PRECISION = 6;

    /**
     * CreateOffer constructor.
     * @param OrderAdapterInterface $orderAdapter
     * @param $clientId
     */
    public function __construct(OrderAdapterInterface $orderAdapter, $clientId)
    {
        $payload = $this->prepareOfferBody($orderAdapter);

        $payload['deal']['client_id'] = $clientId;

        $this->setPayload($payload);
    }

    /**
     * @param OrderAdapterInterface $orderAdapter
     * @return array
     */
    private function prepareOfferBody(OrderAdapterInterface $orderAdapter)
    {
        $orderId = $orderAdapter->getCartId();
        $items = [];
        $cartItems = $orderAdapter->getItems();

        foreach ($cartItems as $item) {
            $price = round((float) $item->getPrice(), self::LINE_ITEMS_PRECISION);
            $quantity = (int) $item->getQtyOrdered();
            $li = [
                'name' => $item->getName(),
                'quantity' => $quantity,
                'amount_fiat' => $price,
            ];

            if($price) {
                $items[] = $li;
            }
        }

        $grandTotal = round($orderAdapter->getGrandTotalAmount(), 2);

        $deal = array(
            'deal' => array(
                'amount_fiat' => $grandTotal,
                'currency_fiat' => $orderAdapter->getCurrencyCode(),
                'line_items' => $items
            ),
            'return_url' => $orderAdapter->getBaseUrl(),
            'external_order_id' => (string) $orderId,
        );

        if ($orderAdapter instanceof CallbackUrlInterface) {
            $deal['callback_url'] = $orderAdapter->getCallbackUrl();
        }

        if ($orderAdapter instanceof ReturnUrlUrlInterface) {
            $deal['return_url'] = $orderAdapter->getReturnUrl();
        }

        return $deal;
    }
}
