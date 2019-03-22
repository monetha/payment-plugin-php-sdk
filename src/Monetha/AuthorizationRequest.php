<?php

namespace Monetha;

use Monetha\Adapter\OrderAdapter;
use Monetha\Services\GatewayService;

class AuthorizationRequest
{
    const EXCEPTION_MESSAGE_MAPPING = [
        'INVALID_PHONE_NUMBER' => 'Invalid phone number',
        'AUTH_TOKEN_INVALID' => 'Monetha plugin setup is invalid, please contact merchant.',
        'INVALID_PHONE_COUNTRY_CODE' => 'This country code is invalid, please input correct country code.',
        'AMOUNT_TOO_BIG' => 'The value of your cart exceeds the maximum amount. Please remove some of the items from the cart.',
        'AMOUNT_TOO_SMALL' => 'amount_fiat in body should be greater than or equal to 0.01',
        'PROCESSOR_MISSING' => 'Can\'t process order, please contact merchant.',
        'UNSUPPORTED_CURRENCY' => 'Selected currency is not supported by Monetha.',
    ];

    private $merchantSecret = '';
    private $monethaApiKey = '';
    private $testMode = false;

    public function __construct()
    {
        $conf = Config::get_configuration();
        $this->testMode = $conf[Config::PARAM_TEST_MODE];
        $this->merchantSecret = $conf[Config::PARAM_MERCHANT_SECRET];
        $this->monethaApiKey = $conf[Config::PARAM_MONETHA_API_KEY];
    }

    /**
     * @param OrderAdapter $orderAdapter
     * @param array $client
     * @return array
     * @throws Response\Exception\IntegrationSecretNotFoundException
     * @throws Response\Exception\ClientIdNotFoundException
     * @throws Response\Exception\OrderIdNotFoundException
     * @throws Response\Exception\OrderNotFoundException
     * @throws Response\Exception\PaymentUrlNotFoundException
     * @throws Response\Exception\TokenNotFoundException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentUrl(OrderAdapter $orderAdapter, array $client)
    {
        $gatewayService = new GatewayService($this->merchantSecret, $this->monethaApiKey, $this->testMode);

        $deal = $gatewayService->prepareOfferBody($orderAdapter);

        $clientResponse =  $gatewayService->createClient($client);
        $clientId = $clientResponse->getClientId();

        // TODO: catch exceptions

        $deal['deal']['client_id'] = $clientId;

        $offerResponse = $gatewayService->createOffer($deal);

        // TODO: catch exceptions

        $executeOfferResponse = $gatewayService->executeOffer($offerResponse);

        // TODO: catch exceptions

        return array('payment_url' => $executeOfferResponse->getPaymentUrl(), 'monetha_id' => $executeOfferResponse->getOrderId());
        //return $executeOfferResponse->order->payment_url;
    }
}
