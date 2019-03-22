<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;


use Monetha\Response\Exception\ClientIdNotFoundException;

class CreateClient extends AbstractResponse
{
    /**
     * @var int
     */
    private $clientId;

    /**
     * CreateClient constructor.
     * @param array $dataResponseItem
     * @throws ClientIdNotFoundException
     */
    public function setResponseArray(array $dataResponseItem)
    {
        parent::setResponseArray($dataResponseItem);

        if (empty($dataResponseItem['client_id'])) {
            throw new ClientIdNotFoundException(
                'Client id not found, response: ' . \GuzzleHttp\json_encode($dataResponseItem)
            );
        }

        $this->clientId = $dataResponseItem['client_id'];
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }
}