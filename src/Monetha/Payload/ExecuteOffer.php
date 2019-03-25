<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:52
 */

namespace Monetha\Payload;

use Monetha\Response\CreateOffer as CreateOfferResponse;

class ExecuteOffer extends AbstractPayload
{
    /**
     * ExecuteOffer constructor.
     * @param CreateOfferResponse $createOfferResponse
     */
    public function __construct(CreateOfferResponse $createOfferResponse)
    {
        $payload = ['token' => $createOfferResponse->getToken()];

        $this->setPayload($payload);
    }
}