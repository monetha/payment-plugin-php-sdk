<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;


use Monetha\Response\Exception\TokenNotFoundException;

class CreateOffer extends AbstractResponse
{
    /**
     * @var string
     */
    private $token;

    /**
     * CreateOffer constructor.
     * @param array $dataResponseItem
     * @throws TokenNotFoundException
     */
    public function setResponseArray(array $dataResponseItem)
    {
        parent::setResponseArray($dataResponseItem);

        if (empty($dataResponseItem['token'])) {
            throw new TokenNotFoundException(
                'Token not found, response: ' . \GuzzleHttp\json_encode($dataResponseItem)
            );
        }

        $this->token = $dataResponseItem['token'];
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}