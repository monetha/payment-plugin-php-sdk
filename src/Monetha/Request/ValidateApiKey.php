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

    // TODO: "L" from SOLID principles
    public function send()
    {
        $responseArray = parent::send();

        $response = new ValidateApiKeyResponse($responseArray);

        return $response;
    }
}