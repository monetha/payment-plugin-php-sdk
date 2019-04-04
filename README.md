# Monetha's payment gateway integration SDK

Use the Monetha plugin to start accepting payments in your e-commerce store.

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

### Sequence diagram

#### Creating an order

#### Canceling an order

### Webhooks

## Simple example

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

## Security

## Contribution

## Changelog
