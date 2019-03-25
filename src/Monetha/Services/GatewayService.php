<?php

namespace Monetha\Services;

use Monetha\Adapter\ClientAdapterInterface;
use Monetha\Adapter\OrderAdapterInterface;
use Monetha\Constants\ApiType;
use Monetha\Constants\Resource;
use Monetha\Constants\EventType;
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

class GatewayService
{
    public $merchantSecret;
    public $mthApiKey;
    public $testMode;

    public function __construct($merchantSecret, $mthApiKey, $testMode)
    {
        $this->merchantSecret = $merchantSecret;
        $this->mthApiKey = $mthApiKey;
        $this->testMode = $testMode;
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

    public function getMerchantId()
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

    public function isJson($str) {
        $json = json_decode($str);
        return $json && $str != $json;
    }

    public function getApiUrl()
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
    public function createClient(ClientAdapterInterface $clientAdapter)
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
    public function createOffer(OrderAdapterInterface $orderAdapter, ClientAdapterInterface $clientAdapter)
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
    public function executeOffer(OrderAdapterInterface $orderAdapter, ClientAdapterInterface $clientAdapter)
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

    public function processAction($order, $data)
    {
        switch ($data->resource) {
            case Resource::ORDER:
                switch ($data->event) {
                    case EventType::CANCELLED:
                        $this->cancelOrder($order, $data->payload->note);
                        break;
                    case EventType::FINALIZED:
                        $this::finalizeOrder($order);
                        break;
                    case EventType::MONEY_AUTHORIZED:
                        $this::finalizeOrderByCard($order);
                        break;
                    default:
                        throw new \Exception('Bad action type');
                        break;
                }
                break;

            default:
            throw new \Exception('Bad resource');
            break;
        }
    }

    public function cancelOrder($order, $note)
    {
        $history = new \OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState(6, (int)($order->id), true);
        $history->save();
    }

    public function finalizeOrder($order)
    {
        $history = new \OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState(2, (int)($order->id), true);
        $history->save();
    }

    public function finalizeOrderByCard($order)
    {
        $history = new \OrderHistory();
        $history->id_order = (int)$order->id;
        $history->changeIdOrderState(2, (int)($order->id), true);
        $history->save();
    }
}
