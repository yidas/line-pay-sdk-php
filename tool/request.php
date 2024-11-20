<?php

require __DIR__ . '/_config.php';

// Get Base URL path without filename
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".dirname($_SERVER['PHP_SELF']);
$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
// Merchant config option
if (isset($input['merchant'])) {
    $merchant = Merchant::getMerchant($input['merchant']);
    $input['channelId'] = $merchant['channelId'];
    $input['channelSecret'] = $merchant['channelSecret'];
}

// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
    'merchantDeviceProfileId' => $input['merchantDeviceProfileId'],
    'subMerchantName' => null,
]);

// Create an order based on Reserve API parameters
$orderParams = [
    "amount" => (integer) $input['amount'],
    "currency" => $input['currency'],
    "orderId" => ($input['orderId']) ? $input['orderId'] : "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3),
    "packages" => [
        [
            "id" => "pid",
            "amount" => (float) $input['amount'],
            "name" => "Package Name",
            "products" => [
                [
                    "name" => $input['productName'],
                    "quantity" => 1,
                    "price" => (integer) $input['amount'],
                    "imageUrl" => ($input['imageUrl']) ? $input['imageUrl'] : 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/LINE_logo.svg/220px-LINE_logo.svg.png',
                ],
            ],
        ],
    ],
    "redirectUrls" => [
        "confirmUrl" => "{$baseUrl}/confirm.php",
        "cancelUrl" => "{$baseUrl}/index.php",
    ],
];

// Capture: false
if (isset($input['captureFalse'])) {
    $orderParams['options']['payment']['capture'] = false;
}
// Preapproved
if (isset($input['preapproved'])) {
    $orderParams['options']['payment']['payType'] = 'PREAPPROVED';
}
// BrachName (Refund API doesn't support)
if ($input['branchName']) {
    $orderParams['options']['extra']['branchName'] = $input['branchName'];
}
// ConfirmUrl
if ($input['confirmUrl']) {
    $orderParams['redirectUrls']['confirmUrl'] = $input['confirmUrl'];
}
// CancelUrl
if ($input['cancelUrl']) {
    $orderParams['redirectUrls']['cancelUrl'] = $input['cancelUrl'];
}
// appPackageName
if ($input['appPackageName']) {
    $orderParams['redirectUrls']['appPackageName'] = $input['appPackageName'];
}
// ConfirmUrlType
if ($input['confirmUrlType']) {
    $orderParams['redirectUrls']['confirmUrlType'] = $input['confirmUrlType'];
    // SERVER confirmUrlType flow
    if ($input['confirmUrlType'] == "SERVER") {
        $confirmServerData = [
            "merchant" => $input['merchant'],
            "amount" => $orderParams['amount'],
            "currency" => $orderParams['currency'],
            "isSandbox" => $input['isSandbox'] ? "true" : "false",
        ];
        $orderParams['redirectUrls']['confirmUrl'] = ($input['confirmUrl']) ? $input['confirmUrl'] : "{$baseUrl}/confirm-server.php?" . http_build_query($confirmServerData);
    }
}
// Display locale
if ($input['locale']) {
    $orderParams['options']['display']['locale'] = $input['locale'];
}
// checkConfirmUrlBrowser
if (isset($input['checkConfirmUrlBrowser'])) {
    $orderParams['options']['display']['checkConfirmUrlBrowser'] = true;
}
// PaymentUrl type
$paymentUrlType = (isset($input['paymenUrlApp'])) ? 'app' : 'web';
// PromotionRestriction
if (is_numeric($input['useLimit']) || is_numeric($input['rewardLimit'])) {
    $orderParams['options']['extra']['promotionRestriction']['useLimit'] = ($input['useLimit']) ? $input['useLimit'] : 0;
    $orderParams['options']['extra']['promotionRestriction']['rewardLimit'] = ($input['rewardLimit']) ? $input['rewardLimit'] : 0;
}
// Events Code
if (isset($input['eventsCode'][0])) {
    for ($i=0; $i < count($input['eventsCode']); $i++) { 
        $orderParams['options']['events'][$i]['code'] = ($input['eventsCode'][$i]) ? $input['eventsCode'][$i] : "";
        $orderParams['options']['events'][$i]['totalAmount'] = ($input['eventsTotalAmount'][$i]) ? (integer) $input['eventsTotalAmount'][$i] : 0;
        $orderParams['options']['events'][$i]['productQuantity'] = ($input['eventsProductQuantity'][$i]) ? (integer) $input['eventsProductQuantity'][$i] : 0;
    }
}
// Request Body Rewriting
$orderParams = ($input['requestBody']) ? json_decode($input['requestBody'], true) : $orderParams;

// Online Reserve API
$response = $linePay->reserve($orderParams);

// Log
saveLog('Request API', $response, true);

// Check Request API result
if (!$response->isSuccessful()) {
    die("<script>alert('ErrorCode {$response['returnCode']}: " . addslashes($response['returnMessage']) . "');history.back();</script>");
}

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => (string) $response["info"]["transactionId"],
    'params' => $orderParams,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
$_SESSION['config'] = $input;

// Redirect to LINE Pay payment URL 
header('Location: '. $response->getPaymentUrl($paymentUrlType) );