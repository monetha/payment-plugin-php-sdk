<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;

use Monetha\Response\CreateOffer as CreateOfferResponse;

class CreateOffer extends AbstractRequest
{
    protected $uri = 'v1/merchants/offer_auth';

    /**
     * @return CreateOfferResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Monetha\Response\Exception\TokenNotFoundException
     */
    public function send()
    {
        $responseArray = $this->makeRequest();

        $response = new CreateOfferResponse($responseArray);

        return $response;
    }
}