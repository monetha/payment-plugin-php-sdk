<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:52
 */

namespace Monetha\Payload;


class CancelOrder extends AbstractPayload
{
    /**
     * CancelOrder constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->setPayload($payload);
    }
}