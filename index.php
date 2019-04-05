<?php

require_once 'vendor/autoload.php';

use Monetha\Adapter\OrderAdapterInterface;
use Monetha\Adapter\InterceptorInterface;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\Adapter\ClientAdapterInterface;
use Monetha\ConfigAdapterTrait;
use Monetha\Services\GatewayService;
use Monetha\Response\Exception\ApiException;
use Monetha\Adapter\WebHookAdapterAbstract;
use Monetha\Adapter\CallbackUrlInterface;
use Monetha\Response\Exception\ValidationException;

class Client implements ClientAdapterInterface {
    private $name;
    private $email;
    private $phone;
    private $zip;
    private $countryIsoCode;
    private $city;
    private $address;

    public function __construct($name, $email, $phone, $zip, $countryIsoCode, $city, $address)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->zip = $zip;
        $this->countryIsoCode = $countryIsoCode;
        $this->city = $city;
        $this->address = $address;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCountryIsoCode()
    {
        return $this->countryIsoCode;
    }

    public function getContactPhoneNumber()
    {
        return $this->phone;
    }

    public function getContactName()
    {
        return $this->name;
    }

    public function getContactEmail()
    {
        return $this->email;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getAddress()
    {
        return $this->address;
    }
}

class Item implements InterceptorInterface {
    private $name;
    private $quantity;
    private $price;

    public function __construct($name, $quantity, $price)
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getQtyOrdered()
    {
        return $this->quantity;
    }

    public function getPrice()
    {
        return $this->price;
    }
}

class Order implements OrderAdapterInterface {
    /**
     * @var InterceptorInterface[]
     */
    private $items = [];

    private $cartId;

    private $grandTotalAmount;

    public function __construct($cartId, $orderItem)
    {
        $this->cartId = $cartId;
        $this->grandTotalAmount = $orderItem['grandTotalAmount'];

        foreach ($orderItem['items'] as $item) {
            $this->items[] = new Item($item['name'], $item['quantity'], $item['price']);
        }
    }

    public function getCartId()
    {
        $this->cartId;
    }

    public function getBaseUrl()
    {
        return 'https://www.example.com';
    }

    public function getCurrencyCode()
    {
        return 'EUR';
    }

    public function getGrandTotalAmount()
    {
        return $this->grandTotalAmount;
    }

    /**
     * @return InterceptorInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }
}

class Config implements ConfigAdapterInterface {
    use ConfigAdapterTrait;

    public function __construct($merchantSecret, $monethaApiKey, $testMode)
    {
        $this->merchantSecret = $merchantSecret;
        $this->monethaApiKey = $monethaApiKey;
        $this->testMode = $testMode;
    }
}

$client = new Client(
    'John Doe',
    'mail@example.com',
    '+370(625)67890',
    '12345',
    'LT',
    'Vilnius',
    'Gedimino prospektas,, 1-23'
    );

$orderItem = [
    'items' => [
        [
            'name' => 'Foo',
            'quantity' => 1,
            'price' => 2,
        ],
        [
            'name' => 'Bar',
            'quantity' => 3,
            'price' => 4,
        ],
    ],

    'grandTotalAmount' => 20,
];

$order = new Order(42, $orderItem);

$config = new Config(
    'MONETHA_SANDBOX_SECRET',
//    'MONETHA_SANDBOX_KEY',
    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjQ2NzgyNTk2MDIsImlhdCI6MTU1NDEyMjAwMiwibmJmIjoxNTU0MTIyMDAyLCJtaWQiOjEsInNjb3BlcyI6WyJ3ZWJob29rcyIsIm9yZGVycyJdfQ.iU4CUz_7Ekua45rMaHanEXhR9I_mgrgN7PSrrOiLlu8',
    true
);

$gateway = new GatewayService($config);

try {
    // optional and could be called only when updating Monetha's API settings
    $gateway->validateApiKey();

    // create an offer (before actual payment step)
    $createOfferResponse = $gateway->createOffer($order, $client);
    $token = $createOfferResponse->getToken();

    // when pressing "Pay now"
    $executeOfferResponse = $gateway->executeOffer($token);

    // getting payment page redirect URL
    $paymentUrl = $executeOfferResponse->getPaymentUrl();

    // the rest information about deal
    $monethaOrder = $executeOfferResponse->getOrder();

} catch(ApiException $e) {
    error_log(
        'Status code: ' . $e->getApiStatusCode() .
        ', error: ' . $e->getApiErrorCode() .
        ', message: ' . $e->getMessage()
    );

    echo $e->getFriendlyMessage();

    return;
}

//header('Location: ' . $paymentUrl);

//echo json_encode($monethaOrder, JSON_PRETTY_PRINT);

// if you want to cancel the order
try {
    $monethaOrderId = $executeOfferResponse->getOrderId();
    $jsonResponse = $gateway->cancelExternalOrder($monethaOrderId)->getResponseJson();
//    var_dump($jsonResponse->order_status->name); // == 'OrderCanceled'

    // do the rest actions on shop side

} catch(ApiException $e) {
    error_log(
        'Status code: ' . $e->getApiStatusCode() .
        ', error: ' . $e->getApiErrorCode() .
        ', message: ' . $e->getMessage()
    );

    echo 'Cannot cancel the order. ' . $e->getFriendlyMessage();

    return;
}

echo 'Order cancelled.';


//
// WebHooks example
//

// It's Mandatory to implement Monetha\Adapter\CallbackUrlInterface in case you wanna receive webhooks
class OrderSupportsWebHooks extends Order implements CallbackUrlInterface {
    /**
     * Monetha Gateway will send JSON payload about the deal and occurred event on that URL.
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getBaseUrl() . '/monetha/webhooks';
    }
}

// Fake order processing
class OrderProcessor {
    public function cancel($orderId, $note) {
        // cancellation on shop side
        return true;
    }

    public function setPaid($orderId, $note) {
        // mark as paid
        return true;
    }
}

/**
 * Class WebHooksProcessor
 *
 * Extend WebHookAdapterAbstract
 * by implementing WebHookAdapterInterface
 */
class WebHooksProcessor extends WebHookAdapterAbstract {
    /**
     * @var OrderProcessor
     */
    private $orderProcessor;

    private $orderId;

    /**
     * @var Config
     */
    private $config;

    /**
     * WebHooksProcessor constructor.
     *
     * @param OrderProcessor $orderProcessor - out fake class
     * @param $orderId - your internal order id
     * @param Config $config - this is ConfigAdapterInterface implementation above
     */
    public function __construct(OrderProcessor $orderProcessor, $orderId, Config $config)
    {
        $this->orderProcessor = $orderProcessor;
        $this->orderId = $orderId;
        $this->config = $config;
    }

    /**
     * Call this method by receiving JSON payload sent by Monetha's Payment Gateway
     */
    public function monethaWebHookHandler() {
        $bodyString = file_get_contents('php://input');
        $signature = !empty($_SERVER['HTTP_MTH_SIGNATURE']) ? $_SERVER['HTTP_MTH_SIGNATURE'] : '';
        try {
            // signature will be checked to ensure that sender is authorized
            // processWebHook is base class method,
            // it will call your finalize, authorize or cancel implementation
            $result = $this->processWebHook($this->config, $bodyString, $signature);
        } catch(ValidationException $e) {
            // in case of signature is invalid or event is unsupported
            error_log($e->getMessage());
            $result = false;
        }

        if ($result) {
            echo 'OK'; // or just send 'No Content' status code like http_response_code(204);
        } else {
            // Send appropriate code to Monetha in case of any error
            http_response_code(500);
        }
    }

    /**
     * @return bool
     */
    public function finalize()
    {
        return $this->orderProcessor->setPaid($this->orderId, 'Order was successfully paid with Monetha Gateway.');
    }

    public function authorize()
    {
        return $this->orderProcessor->setPaid($this->orderId, 'Money on card was successfully authorized by Monetha Gateway.');
    }

    public function cancel($note)
    {
        return $this->orderProcessor->cancel($this->orderId, $note);
    }
}

$orderProcessor = new OrderProcessor();
$webhooksProcessor = new WebHooksProcessor($orderProcessor, 42, $config);

// JSON payload and signature header are being sent, call your handle method
$webhooksProcessor->monethaWebHookHandler();
