<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Request;

use Monetha\Response\ValidateApiKey as ValidateApiKeyResponse;


class ValidateApiKey extends AbstractRequest
{
    protected $method = 'GET';

    /**
     * @return ValidateApiKeyResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Monetha\Response\Exception\IntegrationSecretNotFoundException
     */
    public function send()
    {
        $responseArray = $this->makeRequest();

        $response = new ValidateApiKeyResponse($responseArray);

        return $response;
    }
}