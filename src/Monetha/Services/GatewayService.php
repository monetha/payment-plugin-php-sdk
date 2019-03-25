<?php

namespace Monetha\Services;

use Monetha\Adapter\ClientAdapterInterface;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\Adapter\OrderAdapterInterface;
use Monetha\Constants\ApiType;
use Monetha\Helpers\JWT;
use Monetha\Payload\CancelOrder as CancelOrderPayload;
use Monetha\Payload\CreateClient as CreateClientPayload;
use Monetha\Payload\CreateOffer as CreateOfferPayload;
use Monetha\Payload\ExecuteOffer as ExecuteOfferPayload;
use Monetha\Payload\ValidateApiKey as ValidateApiKeyPayload;
use Monetha\Request\CancelOrder;
use Monetha\Request\CreateClient;
use Monetha\Request\CreateOffer;
use Monetha\Request\ExecuteOffer;
use Monetha\Request\ValidateApiKey;
use Monetha\Response\CreateOffer as CreateOfferResponse;
use Monetha\Response\Exception\ClientIdNotFoundException;
use Monetha\Response\Exception\IntegrationSecretNotFoundException;
use Monetha\Response\Exception\OrderIdNotFoundException;
use Monetha\Response\Exception\OrderNotFoundException;
use Monetha\Response\Exception\PaymentUrlNotFoundException;
use Monetha\Response\Exception\TokenNotFoundException;

class GatewayService
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

    /**
     * @var string
     */
    private $merchantSecret;

    /**
     * @var string
     */
    private $mthApiKey;

    /**
     * @var string
     */
    private $testMode;

    public function __construct(ConfigAdapterInterface $configAdapter)
    {
        $this->merchantSecret = $configAdapter->getMerchantSecret();
        $this->mthApiKey = $configAdapter->getMthApiKey();
        $this->testMode = $configAdapter->getIsTestMode();
    }

    /**
     * @param OrderAdapterInterface $orderAdapter
     * @param ClientAdapterInterface $clientAdapter
     * @return array
     * @throws IntegrationSecretNotFoundException
     * @throws ClientIdNotFoundException
     * @throws OrderIdNotFoundException
     * @throws OrderNotFoundException
     * @throws PaymentUrlNotFoundException
     * @throws TokenNotFoundException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentUrl(OrderAdapterInterface $orderAdapter, ClientAdapterInterface $clientAdapter)
    {
        $executeOfferResponse = $this->executeOffer($orderAdapter, $clientAdapter);

        // TODO: catch exceptions

        return array('payment_url' => $executeOfferResponse->getPaymentUrl(), 'monetha_id' => $executeOfferResponse->getOrderId());
        //return $executeOfferResponse->order->payment_url;
    }

    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function validateApiKey()
    {
        $apiUrl = $this->getApiUrl();
        $merchantId = $this->getMerchantId();

        if ($merchantId == null) {
            return false;
        }

        $uri = 'v1/merchants/' . $merchantId .'/secret';

        $payload = new ValidateApiKeyPayload();
        $request = new ValidateApiKey($payload, $this->mthApiKey, $apiUrl, $uri);

        /** @var \Monetha\Response\ValidateApiKey $response */
        $response = $request->send();
        $integrationSecret = $response->getIntegrationSecret();

        return $integrationSecret == $this->merchantSecret;
    }

    // TODO: decide whether related to PS only

    public function configurationIsValid()
    {
        return (
            !empty($this->merchantSecret) &&
            !empty($this->mthApiKey) &&
            !empty($this->testMode)
        );
    }

    public function validateSignature($signature, $data)
    {
        return $signature == base64_encode(hash_hmac('sha256', $data, $this->merchantSecret, true));
    }

    private function getMerchantId()
    {
        $tks = explode('.', $this->mthApiKey);
        if (count($tks) != 3) {
            return null;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if($this->isJson(JWT::urlsafeB64Decode($bodyb64)))
        {
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
        }
        else
        {
            return null;
        }

        if (isset($payload->mid)) {
            return $payload->mid;
        }

        return null;
    }

    private function isJson($str) {
        $json = json_decode($str);
        return $json && $str != $json;
    }

    private function getApiUrl()
    {
        $apiUrl = ApiType::PROD;

        if ((bool)$this->testMode) {
            $apiUrl = ApiType::TEST;
        }

        return $apiUrl;
    }

    /**
     * @param $orderId
     * @return \Monetha\Response\CancelOrder
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelExternalOrder($orderId)
    {
        $apiUrl = $this->getApiUrl();
        $uri = 'v1/orders/' . $orderId .'/cancel';

        $body = ['cancel_reason'=> 'Order cancelled from shop'];

        $payload = new CancelOrderPayload($body);
        $request = new CancelOrder($payload, $this->mthApiKey, $apiUrl, $uri);

        /** @var \Monetha\Response\CancelOrder $response */
        $response = $request->send();

        return $response;
    }

    /**
     * @param ClientAdapterInterface $clientAdapter
     * @return \Monetha\Response\CreateClient
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createClient(ClientAdapterInterface $clientAdapter)
    {
        $apiUrl = $this->getApiUrl();

        $payload = new CreateClientPayload($clientAdapter);
        $request = new CreateClient($payload, $this->mthApiKey, $apiUrl);

        /** @var \Monetha\Response\CreateClient $response */
        $response = $request->send();

        return $response;
    }

    /**
     * @param OrderAdapterInterface $orderAdapter
     * @param ClientAdapterInterface $clientAdapter
     * @return CreateOfferResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createOffer(OrderAdapterInterface $orderAdapter, ClientAdapterInterface $clientAdapter)
    {
        $clientResponse =  $this->createClient($clientAdapter);
        $clientId = $clientResponse->getClientId();

        // TODO: catch exceptions

        $apiUrl = $this->getApiUrl();

        $payload = new CreateOfferPayload($orderAdapter, $clientId);
        $request = new CreateOffer($payload, $this->mthApiKey, $apiUrl);

        /** @var \Monetha\Response\CreateOffer $response */
        $response = $request->send();

        return $response;
    }

    /**
     * @param OrderAdapterInterface $orderAdapter
     * @param ClientAdapterInterface $clientAdapter
     * @return \Monetha\Response\ExecuteOffer
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function executeOffer(OrderAdapterInterface $orderAdapter, ClientAdapterInterface $clientAdapter)
    {
        $createOfferResponse = $this->createOffer($orderAdapter, $clientAdapter);

        // TODO: catch exceptions

        $payload = new ExecuteOfferPayload($createOfferResponse);

        $apiUrl = $this->getApiUrl();
        $request = new ExecuteOffer($payload, $this->mthApiKey, $apiUrl);

        /** @var \Monetha\Response\ExecuteOffer $response */
        $response = $request->send();

        return $response;
    }
}
