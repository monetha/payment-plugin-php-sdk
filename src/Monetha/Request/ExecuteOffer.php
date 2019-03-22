<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;

use Monetha\Response\ExecuteOffer as ExecuteOfferResponse;

class ExecuteOffer extends AbstractRequest
{
    protected $uri = 'v1/deals/execute';

    /**
     * @return ExecuteOfferResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Monetha\Response\Exception\OrderNotFoundException
     */
    public function send()
    {
        $responseArray = $this->makeRequest();

        $response = new ExecuteOfferResponse($responseArray);

        return $response;
    }
}