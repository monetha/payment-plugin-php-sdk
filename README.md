# Monetha's payment gateway integration SDK <!-- omit in toc -->

Use the Monetha plugin to start accepting payments in your e-commerce store.

- [Installation](#installation)
- [Documentation](#documentation)
  - [Class diagram](#class-diagram)
  - [Sequence diagram](#sequence-diagram)
- [Simple example](#simple-example)
  - [Creating an order](#creating-an-order)
  - [Canceling an order](#canceling-an-order)
    - [Webhooks](#webhooks)
- [Security](#security)
- [Contribution](#contribution)
- [Changelog](#changelog)

## Installation

```sh
composer config repositories.monetha/payment-plugin-php-sdk vcs https://gitlab.com/monetha/payment-plugin-php-sdk.git
```

```sh
composer require monetha/payment-plugin-php-sdk
```

## Documentation

In order to start integration you have to just implement 4 interfaces:

1. `Monetha/Adapter/ConfigAdapterInterface.php` - to retrieve/validate API key etc.
2. `Monetha/Adapter/ClientAdapterInterface.php` returns buyer information
3. `Monetha/Adapter/OrderAdapterInterface.php` provides order information
4. `Monetha/Adapter/InterceptorInterface.php` is a single item from the order. 

### Class diagram

![UML](example/payment-plugin-php-sdk.png "UML diagram")

### Sequence diagram

![UML](example/workflow.png "Workflow")

## Simple example

### Creating an order

Full example of the code below can be found in [/index.php](/index.php).

```php

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
```

### Canceling an order

A common behavior could be that e-shop administrator needs to cancel Order. An example of how this can be achieved shown below

```php
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
```

#### Webhooks

Monetha's Payment Gateway triggers e-shop webhook url in order to sync order status between systems. The following events trigger a webhook:

* order.canceled
* order.finalized
* order.money_authorized

In order to handle Webhook you have to

1. Extend `Monetha\Adapter\WebHookAdapterAbstract` class by implementing 3 appropriate abstract methods:
* `cancel()` - what to do in case if order was canceled through mth-api call
* `finalize()` - ...order was paid on the payment page where used was redirected
* `authorize()` - ...order was paid by card (authorization was successful)

2. Your class that implements `Monetha/Adapter/OrderAdapterInterface.php` needs to implement`Monetha\Adapter\CallbackUrlInterface` as well (it's only method should return the endpoint URL where Monetha will send JSON data in case of the events above).

3. Process incoming request in the way below:

```php
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
```

Full example of the code can be found in /index.php.

## Security

## Contribution

## Changelog
