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

// Sign up at Monetha to become a Merchant - https://help.monetha.io/hc/en-us/categories/360000271031#article=Preliminary-steps
// After completing the sign up you visit your Merchant Cabinet
// Payment > Payment settings and copy paste the following
$merchantSecret = 'MONETHA_MERCHANT_SECRET'; 
$apiKey = 'MONETHA_MERCHANT_API_TOKEN'; 

// testMode - is a flag describing that shop will be run in Ropsten.
// Meaning that no true crypto currency will be used
$testMode = true; 

// by using Monetha\ConfigAdapterTrait inside Config class
// and setting those private variables from arguments,
// you are actually implementing Monetha\Adapter\ConfigAdapterInterface
// which is required to construct Monetha\Services\GatewayService
$config = new Config(
    $merchantSecret,
    $apiKey,
    $testMode
);

Monetha

$gateway = new GatewayService($config);

try {
    // optional and could be called only when updating Monetha's API settings
    $gateway->validateApiKey();

    // Prepare an Offer by signing the Order information
    // This information will be used during Order execution
    // You might need to repeat this step if your order information is updated
    // It is advised to execute this API call on a Checkout page
    $createOfferResponse = $gateway->createOffer($order, $client);
    $token = $createOfferResponse->getToken();

    // Execute the signed Offer. It is best to execute following method 
    // on “Pay now” button press
    $executeOfferResponse = $gateway->executeOffer($token);

    // After an Order was executed an E-shop must redirect a user
    // to payment page. A payment url is unique for each order 
    // and can be retrieved as follows
    $paymentUrl = $executeOfferResponse->getPaymentUrl();

    // It is best to retrieve and store a back reference to 
    // Monetha Order. You can achieve that by reading the response payload
    // of Order execution call
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

header('Location: ' . $paymentUrl);

// Order cancellation example 
try {
    $monethaOrderId = $executeOfferResponse->getOrderId();
    $jsonResponse = $gateway->cancelExternalOrder($monethaOrderId)->getResponseJson();
    if ($jsonResponse->order_status->name == 'OrderCanceled') {
        // TODO: Make any shop specific actions
        echo 'Order cancelled.';
    }

} catch(ApiException $e) {
    error_log(
        'Status code: ' . $e->getApiStatusCode() .
        ', error: ' . $e->getApiErrorCode() .
        ', message: ' . $e->getMessage()
    );

    echo 'Cannot cancel the order. ' . $e->getFriendlyMessage();

    return;
}

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
     * Call this method after receiving JSON payload sent by Monetha's Payment Gateway
     */
    public function monethaWebHookHandler() {
        $bodyString = file_get_contents('php://input');
        $signature = !empty($_SERVER['HTTP_MTH_SIGNATURE']) ? $_SERVER['HTTP_MTH_SIGNATURE'] : '';
        try {
            // To ensure that Monetha sent the webhook and no one else Monetha sends a
            // MTH-SIGNATURE header together with the webhook payload
            // processWebHook() is base a class method handling 
            // the validation of the signature and will call
            // finalize, authorize or cancel implementation depending on the event type
            $result = $this->processWebHook($this->config, $bodyString, $signature);
        } catch(ValidationException $e) {
            // Exception is thrown in case of signature is invalid or event is unsupported
            error_log($e->getMessage());
            $result = false;
        }

        if ($result) {
            // Monetha is expecting either status code 200 or 204
            http_response_code(204); 
        } else {
            // Monetha is expecting a status code 500 in case of an error
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
