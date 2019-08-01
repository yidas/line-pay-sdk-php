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
]);

// Create an order based on Reserve API parameters
$orderParams = [
    "amount" => (integer) $input['amount'],
    "currency" => $input['currency'],
    "orderId" => "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3),
    "packages" => [
        [
            "id" => "pid",
            "amount" => (integer) $input['amount'],
            "name" => "Package Name",
            "products" => [
                [
                    "name" => $input['productName'],
                    "quantity" => 1,
                    "price" => (integer) $input['amount'],
                    "imageUrl" => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/LINE_logo.svg/220px-LINE_logo.svg.png',
                ],
            ],
        ],
    ],
    "redirectUrls" => [
        "confirmUrl" => "{$baseUrl}/confirm.php",
        "cancelUrl" => "{$baseUrl}/index.php",
    ],
    "options" => [  
        "display" => [
            "checkConfirmUrlBrowser" => true,
        ],
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
// PaymentUrl type
$paymentUrlType = (isset($input['paymenUrlApp'])) ? 'app' : 'web';

// Online Reserve API
$response = $linePay->reserve($orderParams);

// Log
saveLog('Request API', $orderParams, null, $response->toArray(), null, true);

// Check Reserve API result
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