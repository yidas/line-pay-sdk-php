<p align="center">
    <a href="https://pay.line.me/" target="_blank">
        <img src="https://scdn.line-apps.com/linepay/partner/images/sp_txt_shape_v4_2.png" width="126px">
    </a>
    <h1 align="center">LINE Pay SDK <i>for</i> PHP</h1>
    <br>
</p>

LINE Pay SDK for PHP

[![Latest Stable Version](https://poser.pugx.org/yidas/line-pay-sdk/v/stable?format=flat-square)](https://packagist.org/packages/yidas/line-pay-sdk)
[![License](https://poser.pugx.org/yidas/line-pay-sdk/license?format=flat-square)](https://packagist.org/packages/yidas/line-pay-sdk)

English | [繁體中文](https://github.com/yidas/line-pay-sdk-php/blob/master/README-zh_TW.md)

OUTLINE
-------

- [Demonstration](#demonstration)
- [Requirements](#requirements)
    - [Authentication](#authentication)
- [Installation](#installation)
- [Usage](#usage)
    - [Client](#client)
        - [Device Information](#device-information)
    - [Response](#response)
        - [Retrieving Data](#retrieving-data)
            - [Retrieving as Object](#retrieving-as-object)
            - [Retrieving as Array](#retrieving-as-array)
        - [Methods](#methods)
            - [isSuccessful()](#issuccessful)
            - [toArray()](#toarray)
            - [toObject()](#toobject)
            - [getPayInfo()](#getpayinfo)
    - [Online APIs](#online-apis)
        - [Get Payment Details API](#get-payment-details-api)
        - [Reserve Payment API](#reserve-payment-api)
        - [Payment Confirm API](#payment-confirm-api)
        - [Refund Payment API](#refund-payment-api)
        - [Get Authorization Details API](#get-authorization-details-api)
        - [Capture API](#capture-api)
        - [Void Authorization API](#void-authorization-api)
        - [Preapproved Payment API](#preapproved-payment-api)
        - [Check regKey Status API](#check-regkey-status-api)
        - [Expire regKey API](#expire-regkey-api)
    - [Offline APIs](#offline-apis)
        - [Payment](#payment)
        - [Payment Status Check](#payment-status-check)
        - [Void](#void)
        - [Capture](#capture)
        - [Refund](#refund)
        - [Authorization Details](#authorization-details)
- [Resources](#resources)
- [References](#references)

---

DEMONSTRATION
-------------

[Sample Codes Site for LINE Pay (Reserve, Confirm, Refund)](https://github.com/yidas/line-pay-sdk-php/tree/master/sample)

```php
// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => 'Your merchant X-LINE-ChannelId',
    'channelSecret' => 'Your merchant X-LINE-ChannelSecret',
    'isSandbox' => true, 
]);

// Online Reserve API
$response = $linePay->reserve([
    'productName' => 'Your product name',
    'amount' => 250,
    'currency' => 'TWD',
    'confirmUrl' => 'https://yourname.com/line-pay/confirm',
    'productImageUrl' => 'https://yourname.com/assets/img/product.png',
    'cancelUrl' => 'https://yourname.com/line-pay/cancel',
    'orderId' => 'Your order ID',
]);

// Check Reserve API result (returnCode "0000" check method)
if (!$response->isSuccessful()) {
    throw new Exception("ErrorCode {$response['returnCode']}: {$response['returnMessage']}");
}

// Redirect to LINE Pay payment URL 
header('Location: '. $response->getPaymentUrl() );
```

---


REQUIREMENTS
------------
This library requires the following:

- PHP 5.4.0+\|7.0+
- guzzlehttp/guzzle 5.3.1+\|6.0+
- [LINE Pay merchant authentication](#authentication)

### Authentication

Each LINE Pay merchant Requires authentication information for LINE Pay integration is as below.
- channel id
- channel secret key

To get an LINE Pay Authentication:

 1. Merchant's verification information can be viewed at [LINE Pay Merchant Center](http://pay.line.me) after evaluation process is
complete.
 2. Login into [LINE Pay Merchant Center](http://pay.line.me), then **get the ChannelId/ChannelSecret** (Payment Integration Management > Manage Link Key).
 3. In [LINE Pay Merchant Center](http://pay.line.me), **set IP white list** for your servers (Payment Integration Management > Manage Payment Server IP).

> You can immediately create a sandbox merchant account for test through [LINE Pay Sandbox Creation](https://pay.line.me/tw/developers/techsupport/sandbox/creation?locale=en_US).

---

INSTALLATION
------------

Run Composer in your project:

    composer require yidas/line-pay-sdk
    
Then you could use SDK class after Composer is loaded on your PHP project:

```php
require __DIR__ . '/vendor/autoload.php';

use yidas\linePay\Client;
```

---

USAGE
-----

Before using any API methods, first you need to create a Client with configuration, then use the client to access LINE Pay API methods.

### Client

Create a LINE Pay Client with [API Authentication](#api-authentication):

```php
$linePay = new \yidas\linePay\Client([
    'channelId' => 'Your merchant X-LINE-ChannelId',
    'channelSecret' => 'Your merchant X-LINE-ChannelSecret',
    'isSandbox' => true, 
]);
```

#### Device Information

You could set device information for Client (Optional):

```php
$linePay = new \yidas\linePay\Client([
    'channelId' => 'Your merchant X-LINE-ChannelId',
    'channelSecret' => 'Your merchant X-LINE-ChannelSecret',
    'isSandbox' => ($env=='real') ? false : true, 
    'merchantDeviceType' => 'Device type string',
    'merchantDeviceProfileId' => 'Device profile ID string',
]);
```

### Response

Each API methods will return `yidas\linePay\Response` object, which can retrieve data referred to LINE Pay JSON response data structure.

#### Retrieving Data

Response object provides response body data accessing by object attributes or array keys:

##### Retrieving as Object

```php
// Get LINE Pay results code from response
$returnCode = $response->returnCode;
// Get LINE Pay info.payInfo[] from response
$payinfo = $response->info->payinfo;
```

##### Retrieving as Array

```php
// Get LINE Pay results code from response
$returnCode = $response['returnCode'];
// Get LINE Pay info.payInfo[] from response
$payinfo = $response['info']['payinfo'];
```

#### Methods

Response object has some helpful methods to use:

##### isSuccessful()

LINE Pay API result successful status (Check that returnCode is "0000")

*Example:*
```php
if (!$response->isSuccessful()) {
    
    throw new Exception("Code {$response['returnCode']}: {$response['returnMessage']}");
}
```

##### getPaymentUrl()

Get LINE Pay API response body's info.paymentUrl (Default type is "web")

##### getPayInfo()

Get LINE Pay API response body's info.payInfo[] or info.[$param1].payInfo[] as array

##### toArray()

Get LINE Pay response body as array

##### toObject()

Get LINE Pay response body as object

### Online APIs

For Web integration. Merchant will reserves a payment and generates payment URL(QR code) to customer to scan by LINE App.

> Flow: [`Reserve`](#reserve-payment-api) -> [`Confirm`](#payment-confirm-api) -> [`Details`](#get-payment-details-api) -> [`Refund`](#refund-payment-api)

#### Get Payment Details API

Gets the details of payments made with LINE Pay. This API only gets the payments that have been captured.

```php
public Response details(array $queryParams=null)
```

*Example:*
```php
$response = $linePay->details([
    "transactionId" => [$transactionId],
]);
```

#### Reserve Payment API

Prior to processing payments with LINE Pay, the Merchant is evaluated if it is a normal Merchant store then the information is reserved for payment. 
When a payment is successfully reserved, the Merchant gets a "transactionId" that is a key value used until the payment is completed or refunded.

```php
public Response reserve(array $optParams=null)
```

*Example:*
```php 
$response = $linePay->reserve([
    'productName' => 'Your product name',
    'amount' => 250,
    'currency' => 'TWD',
    'confirmUrl' => 'https://yourname.com/line-pay/confirm',
    'productImageUrl' => 'https://yourname.com/assets/img/product.png',
    'cancelUrl' => 'https://yourname.com/line-pay/cancel',
    'orderId' => 'Your order ID',
]);
```

#### Payment Confirm API

This API is used for a Merchant to complete its payment. The Merchant must call Confirm Payment API to actually complete the payment. 
However, when "capture" parameter is "false" on payment reservation, the payment status becomes AUTHORIZATION, and the payment is completed only after "Capture API" is called.

```php
public Response confirm(integer $transactionId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->confirm($transactionId, [
    "amount" => 250,
    "currency" => 'TWD',
]);
```

#### Refund Payment API

Requests refund of payments made with LINE Pay. 
To refund a payment, the LINE Pay user's payment transactionId must be forwarded. A partial refund is also possible depending on the refund amount.

```php
public Response refund(integer $transactionId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->refund($transactionId);
```

*For Partial refund:*
```php
$response = $linePay->refund($transactionId, [
    'refundAmount' => 200,
]);
```

#### Get Authorization Details API

Gets the details authorized with LINE Pay. This API only gets data that is authorized or whose authorization is voided; the one that is already captured can be viewed by using "Get Payment Details API”. 

```php
public Response authorizations(array $queryParams=null)
```

*Example:*
```php
$response = $linePay->authorizations([
    "transactionId" => [$transactionId],
]);
```

#### Capture API

If "capture" is "false" when the Merchant calls the “Reserve Payment API” , the payment is completed only after the Capture API is called.

```php
public Response authorizationsCapture(integer $transactionId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->authorizationsCapture($transactionId, [
    "amount" => 250,
    "currency" => 'TWD',
]);
```

#### Void Authorization API

Voids a previously authorized payment. A payment that has been already captured can be refunded by using the “Refund Payment API”.

```php
public Response authorizationsVoid(integer $transactionId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->authorizationsVoid($transactionId);
```

#### Preapproved Payment API

When the payment type of the Reserve Payment API was set as PREAPPROVED, a regKey is returned with the payment result. 
Preapproved Payment API uses this regKey to directly complete a payment without using the LINE app.

```php
public Response preapproved(integer $regKey, array $optParams=null)
```

*Example:*
```php
$response = $linePay->preapproved([
    'productName' => 'Your product name',
    'amount' => 250,
    'currency' => 'TWD',
    'orderId' => 'Your order ID',
]);
```

#### Check regKey Status API

Checks if regKey is available before using the preapproved payment API.

```php
public Response preapprovedCheck(integer $regKey, array $queryParams=null)
```

*Example:*
```php
$response = $linePay->preapprovedCheck($regKey);
```

#### Expire regKey API

Expires the regKey information registered for preapproved payment. Once the API is called, the regKey is no longer used for preapproved payments.

```php
public Response preapprovedExpire(integer $regKey, array $optParams=null)
```

*Example:*
```php
$response = $linePay->preapprovedExpire($regKey);
```

### Offline APIs

For POS integration. Customer presents their barcode or QR code to merchants to scan at the POS machine.

> Flow: [`OneTimeKeysPay`](#payment) -> [`OrdersCheck`](#payment-status-check) -> [`OrdersRefund`](#refund)

#### Payment

This API is to process payment by reading MyCode provided from LINE Pay App with Merchant's device.

```php
public Response oneTimeKeysPay(array $optParams=null)
```

*Example:*
```php
$response = $linePay->oneTimeKeysPay([
    'productName' => 'Your product name',
    'amount' => 250,
    'currency' => 'TWD',
    'productImageUrl' => 'https://yourname.com/assets/img/product.png',
    'orderId' => 'Your order ID',
    "oneTimeKey"=> 'LINE Pay MyCode',
]);
```

#### Payment Status Check

It's the API used when the final payment status can't be checked due to read timeout.
- The status needs to be checked by calling it at regular intervals and 3~5 seconds are recommended for the interval time.
- Payment valid time is maximum 20 minutes and it's calculated from the Payment API Response time. Therefore, a merchant should check the payment status for maximum 20 minutes. In case 20 minutes are exceeded, that transaction will be the payment that couldn't be completed due to exceeded valid time.

```php
public Response ordersCheck(string $orderId, array $$queryParams=null)
```

*Example:*
```php
$response = $linePay->ordersCheck($orderId);
```

#### Void

This API is to void the authorization.

```php
public Response ordersVoid(string $orderId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->ordersVoid($orderId);
```

#### Capture

This API is to capture the authorized transaction.

```php
public Response ordersCapture(string $orderId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->ordersCapture($orderId);
```

#### Refund

This API is to refund after the payment completion (Captured data).

```php
public Response ordersRefund(string $orderId, array $optParams=null)
```

*Example:*
```php
$response = $linePay->ordersRefund($orderId);
```

#### Authorization Details

Refer to [Online Get Authorization Details API](#get-authorization-details-api)

This API is to search the authorization details. Only authorized or cancelled (Void or Expire) data can be searched and the data after capturing can be searched by The Payment Details API.

*Example:*
```php
$response = $linePay->authorizations([
    "orderId" => $orderId,
]);
```

---

RESOURCES
=========

[LINE Pay Integration Guide v1.1.2 (EN)](https://pay.line.me/file/guidebook/technicallinking/LINE_Pay_Integration_Guide_for_Merchant-v1.1.2-EN.pdf)

[LINE Pay Integration Guide (For Offline Merchant) application email](mailto:dl_tech_support_global@linecorp.com)

[LINE Pay Sandbox creation](https://pay.line.me/tw/developers/techsupport/sandbox/creation?locale=en_US)

[LINE Pay OneTimeKeys Simulation](https://sandbox-web-pay.line.me/web/sandbox/payment/otk)

[LINE Pay OneTimeKeys Simulation (For TW Merchant)](https://sandbox-web-pay.line.me/web/sandbox/payment/oneTimeKey?countryCode=TW&paymentMethod=card&preset=1)

---

REFERENCES
==========

[LINE Pay Documentation](https://pay.line.me/tw/developers/documentation/download/tech?locale=en_US)
