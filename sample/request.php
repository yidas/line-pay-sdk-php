<?php

require __DIR__ . '/_config.php';

// Get Base URL path without filename
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".dirname($_SERVER['PHP_SELF']);
$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;

// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
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
                    "imageUrl" => 'https://scdn.line-apps.com/linepay/partner/images/sp_txt_shape_v4_2.png',
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
header('Location: '. $response->getPaymentUrl() );