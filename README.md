# Monetha's payment gateway integration SDK

Use the Monetha plugin to start accepting payments in your e-commerce store.

## Installation

```shell
composer config repositories.monetha/payment-plugin-php-sdk vcs https://gitlab.com/monetha/payment-plugin-php-sdk.git
```

```shell
composer require monetha/payment-plugin-php-sdk
```

## Documentation

In order to start integration you have to just implement 4 interfaces:

1. `Monetha/Adapter/ConfigAdapterInterface.php` - to retrieve/validate API key etc.
2. `Monetha/Adapter/ClientAdapterInterface.php` returns buyer information
3. `Monetha/Adapter/OrderAdapterInterface.php` provides order information
4. `Monetha/Adapter/InterceptorInterface.php` is a single item from the order. 

### Class diagram

### Sequence diagram

## Simple example

#### Creating an order

```php
$gateway = new Monetha\Services\GatewayService($config);

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

} catch(Monetha\Response\Exception\ApiException $e) {
    error_log(
        'Status code: ' . $e->getApiStatusCode() .
        ', error: ' . $e->getApiErrorCode() .
        ', message: ' . $e->getMessage()
    );

    echo $e->getFriendlyMessage();

    return;
}

// redirect
header('Location: ' . $paymentUrl);
```

Full examples is inside `/index.php`.

#### Canceling an order
```php
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
```
#### Webhooks

Monetha's Payment Gateway supports webhooks during such events on it's side like

* order.canceled
* order.finalized
* order.money_authorized

All you need to do in order to support Webhooks' receiving - just extend `Monetha\Adapter\WebHookAdapterAbstract` class by implementing 3 appropriate abstract methods:
* `cancel()` - what to do in case if order was canceled through mth-api call
* `finalize()` - ...order was paid
* `authorize()` - ...order was paid by card

## Security

## Contribution

## Changelog
