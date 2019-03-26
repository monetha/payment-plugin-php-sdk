<?php
/**
 * Created by PhpStorm.
 * User: hitrov
 * Date: 2019-03-21
 * Time: 14:51
 */

namespace Monetha\Response;


use Monetha\Response\Exception\IntegrationSecretNotFoundException;

class ValidateApiKey extends AbstractResponse
{
    /**
     * @var string
     */
    private $integrationSecret;

    /**
     * ValidateApiKey constructor.
     * @param array $dataResponseItem
     * @throws IntegrationSecretNotFoundException
     */
    public function setResponseArray(array $dataResponseItem)
    {
        parent::setResponseArray($dataResponseItem);

        if (empty($dataResponseItem['integration_secret'])) {
            throw new IntegrationSecretNotFoundException(
                'Integration secret not found, response: ' . json_encode($dataResponseItem)
            );
        }

        $this->integrationSecret = $dataResponseItem['integration_secret'];
    }

    /**
     * @return string
     */
    public function getIntegrationSecret()
    {
        return $this->integrationSecret;
    }
}