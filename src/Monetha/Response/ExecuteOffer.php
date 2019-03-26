<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;


use Monetha\Response\Exception\OrderIdNotFoundException;
use Monetha\Response\Exception\OrderNotFoundException;
use Monetha\Response\Exception\PaymentUrlNotFoundException;

class ExecuteOffer extends AbstractResponse
{
    /**
     * @var array
     */
    private $order;

    /**
     * ExecuteOffer constructor.
     * @param array $dataResponseItem
     * @throws OrderNotFoundException
     */
    public function setResponseArray(array $dataResponseItem)
    {
        parent::setResponseArray($dataResponseItem);

        if (empty($dataResponseItem['order'])) {
            throw new OrderNotFoundException(
                'Order not found, response: ' . json_encode($dataResponseItem)
            );
        }

        $this->order = $dataResponseItem['order'];
    }

    /**
     * @return string
     * @throws PaymentUrlNotFoundException
     */
    public function getPaymentUrl()
    {
        if (empty($this->order['payment_url'])) {
            throw new PaymentUrlNotFoundException('Payment url not found, order: ' . json_encode($this->order));
        }

        return $this->order['payment_url'];
    }

    /**
     * @return string
     * @throws OrderIdNotFoundException
     */
    public function getOrderId()
    {
        if (empty($this->order['id'])) {
            throw new OrderIdNotFoundException('Order id not found, order: ' . json_encode($this->order));
        }

        return $this->order['id'];
    }
}