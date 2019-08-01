Request API

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://sandbox-api-pay.line.me',
    'headers' => [
        'Content-Type' => 'application/json',
        'X-LINE-ChannelId' => '{your channelId}',
        'X-LINE-Authorization-Nonce' => '44453d45-768e-40e8-8349-748e797c450f',
        'X-LINE-Authorization' => '{Hmac signature}',
        'X-LINE-MerchantDeviceProfileId' => '{your device profile id}',
    ],
]);

$response = $client->request('POST', '/v3/payments/request', [
    'amount' => 250,
    'currency' => 'TWD',
    'orderId' => 'Your order ID',
    'packages' => [
        [
            'id' => 'Your package ID',
            'amount' => 250,
            'name' => 'Your package name',
            'products' => [
                [
                    'name' => 'Your product name',
                    'quantity' => 1,
                    'price' => 250,
                    'imageUrl' => 'https://yourname.com/assets/img/product.png',
                ],
            ],
        ],
    ],
    'redirectUrls' => [
        'confirmUrl' => 'https://yourname.com/line-pay/confirm',
        'cancelUrl' => 'https://yourname.com/line-pay/cancel',
    ],
]);
```

HMAC

```php
<?php

$channelSecret = 'a917ab6a2367b536f8e5a6e2977e06f4';
$requestUri = '/v3/payments/request';
$jsonBody = json_encode([
    'amount' => 250,
    'currency' => 'TWD',
    'orderId' => 'Your order ID',
    'packages' => [
        [
            'id' => 'Your package ID',
            'amount' => 250,
            'name' => 'Your package name',
            'products' => [
                [
                    'name' => 'Your product name',
                    'quantity' => 1,
                    'price' => 250,
                    'imageUrl' => 'https://yourname.com/assets/img/product.png',
                ],
            ],
        ],
    ],
    'redirectUrls' => [
        'confirmUrl' => 'https://yourname.com/line-pay/confirm',
        'cancelUrl' => 'https://yourname.com/line-pay/cancel',
    ],
]);

$authNonce = date('c'); // ISO 8601 date
$authMacText = $channelSecret . $requestUri . $jsonBody . $authNonce;
$headerAuthorization = base64_encode(hash_hmac('sha256', $authMacText, $channelSecret , true));
$headerAuthorizationNonce = $authNonce;
```
