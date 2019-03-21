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

    // TODO: "L" from SOLID principles

    /**
     * @return array|CreateClientResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Monetha\Response\Exception\ClientIdNotFoundException
     */
    public function send()
    {
        $responseArray = parent::send();

        $response = new CreateClientResponse($responseArray);

        return $response;
    }
}