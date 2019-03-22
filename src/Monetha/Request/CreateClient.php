<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;

use Monetha\Response\CreateClient as CreateClientResponse;

class CreateClient extends AbstractRequest
{
    protected $uri = 'v1/clients';

    /**
     * @return CreateClientResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Monetha\Response\Exception\ClientIdNotFoundException
     */
    public function send()
    {
        $responseArray = $this->makeRequest();

        $response = new CreateClientResponse($responseArray);

        return $response;
    }
}