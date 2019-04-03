<?php

require_once 'vendor/autoload.php';

use Monetha\Adapter\OrderAdapterInterface;
use Monetha\Adapter\InterceptorInterface;
use Monetha\Adapter\ConfigAdapterInterface;
use Monetha\Adapter\ClientAdapterInterface;
use Monetha\ConfigAdapterTrait;
use Monetha\Services\GatewayService;
use Monetha\Response\Exception\ApiException;

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
        return 'Gedimino prospektas,, 1-23';
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
    'MONETHA_SANDBOX_KEY',
    true
);

$gateway = new GatewayService($config);

try {
    $gateway->validateApiKey();

    $createOfferResponse = $gateway->createOffer($order, $client);
    $token = $createOfferResponse->getToken();

    $executeOfferResponse = $gateway->executeOffer($token);

    $paymentUrl = $executeOfferResponse->getPaymentUrl();
    $monethaOrder = $executeOfferResponse->getOrder();

} catch(ApiException $e) {
    error_log(
        'Status code: ' . $e->getApiStatusCode() .
        ', error: ' . $e->getApiErrorCode() .
        ', message: ' . $e->getApiErrorMessage()
    );

//    var_dump($e);
    echo $e->getFriendlyMessage();

    return;
}

echo json_encode($monethaOrder, JSON_PRETTY_PRINT);