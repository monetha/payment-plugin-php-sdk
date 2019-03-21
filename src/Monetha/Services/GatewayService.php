<?php

namespace Monetha\Services;

use Monetha\Interceptor;
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

    public function prepareOfferBody($order, $orderId)
    {
        $items = [];
        $cartItems = $order->getItems();

        $itemsPrice = 0;
        foreach ($cartItems as $item) {
            /**
             * @var $item Interceptor
             */
            $price = round($item->getPrice(), 2);
            $quantity = $item->getQtyOrdered();
            $li = [
                'name' => $item->getName(),
                'quantity' => $quantity,
                'amount_fiat' => $price,
            ];
            $itemsPrice += $price * $quantity;
            if($price > 0)
            {
                $items[] = $li;
            }
        }

        $itemsPrice = round($itemsPrice, 2);

        $grandTotal = round($order->getGrandTotalAmount(), 2);

        // Add shipping and taxes
        $shipping = [
            'name' => 'Shipping and taxes',
            'quantity' => 1,
            'amount_fiat' => round($grandTotal - $itemsPrice, 2),
        ];
        
        if($shipping['amount_fiat'] > 0)
        {
            $items[] = $shipping;
        }

        $deal = array(
            'deal' => array(
                'amount_fiat' => round($grandTotal, 2),
                'currency_fiat' => $order->getCurrencyCode(),
                'line_items' => $items
            ),
            'return_url' => $order->getBaseUrl(),
            'callback_url' => $order->getBaseUrl() . '/modules/monethagateway/webservices/actions.php',
            'external_order_id' => $orderId . " ",
        );        
        return $deal;
    }

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

        // TODO: try/catch
        $validateResponse = $request->send();
        $integrationSecret = $validateResponse->getIntegrationSecret();

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

    public function cancelExternalOrder($orderId)
    {
        $apiUrl = $this->getApiUrl();
        $uri = 'v1/orders/' . $orderId .'/cancel';

        $body = ['cancel_reason'=> 'Order cancelled from shop'];

        $payload = new CancelOrderPayload($body);
        $request = new CancelOrder($payload, $this->mthApiKey, $apiUrl, $uri);
        $guzzleResponse = $request->send();

        $apiUrl = $apiUrl . $uri;
        return HttpService::callApi($apiUrl, 'POST', $body, ["Authorization: Bearer " . $this->mthApiKey]);
    }

    public function createClient($clientBody)
    {
        $apiUrl = $this->getApiUrl();

        $payload = new CreateClientPayload($clientBody);
        $request = new CreateClient($payload, $this->mthApiKey, $apiUrl);
        $response = $request->send();

        $clientId = $response->getClientId();

        return $clientId;
    }

    public function createOffer($offerBody)
    {
        $apiUrl = $this->getApiUrl();

        $payload = new CreateOfferPayload($offerBody);
        $request = new CreateOffer($payload, $this->mthApiKey, $apiUrl);
        $guzzleResponse = $request->send();

        $apiUrl = $apiUrl . 'v1/merchants/offer_auth';

        return HttpService::callApi($apiUrl, 'POST', $offerBody, ["Authorization: Bearer " . $this->mthApiKey]);
    }

    public function executeOffer($token)
    {
        $apiUrl = $this->getApiUrl();
        $body = ["token" => $token];

        $payload = new ExecuteOfferPayload($body);
        $request = new ExecuteOffer($payload, $this->mthApiKey, $apiUrl);
        $guzzleResponse = $request->send();

        $apiUrl = $apiUrl . 'v1/deals/execute';

        return HttpService::callApi($apiUrl, 'POST', $body, []);
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
