<?php

use yidas\linePay\Client;
use yidas\linePay\exception\ConnectException;

require __DIR__ . '/_config.php';

$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
$input['otk'] = trim(str_replace(' ', '', $input['otk']));
// Merchant config option
if (isset($input['merchant'])) {
    $merchant = Merchant::getMerchant($input['merchant']);
    $input['channelId'] = $merchant['channelId'];
    $input['channelSecret'] = $merchant['channelSecret'];
}

// Create LINE Pay client
$linePay = new Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
    'merchantDeviceProfileId' => $input['merchantDeviceProfileId'],
]);

// Create an order based on Reserve API parameters
$orderId = ($input['orderId']) ? $input['orderId'] : "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3);
$orderParams = [
    'amount' => (integer) $input['amount'],
    'currency' => $input['currency'],
	'orderId' => $orderId,
    'oneTimeKey' => $input['otk'],
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
];

// Capture: false
if (isset($input['captureFalse'])) {
    $orderParams['options']['payment']['capture'] = false;
}
// BrachName (Refund API doesn't support)
if ($input['branchName']) {
    $orderParams['extras']['branchName'] = $input['branchName'];
}
// PromotionRestriction
if (is_numeric($input['useLimit']) || is_numeric($input['rewardLimit'])) {
    $orderParams['extras']['promotionRestriction']['useLimit'] = ($input['useLimit']) ? $input['useLimit'] : 0;
    $orderParams['extras']['promotionRestriction']['rewardLimit'] = ($input['rewardLimit']) ? $input['rewardLimit'] : 0;
}
// Events Code
if (isset($input['eventsCode'][0])) {
    for ($i=0; $i < count($input['eventsCode']); $i++) { 
        $orderParams['extras']['events'][$i]['code'] = ($input['eventsCode'][$i]) ? $input['eventsCode'][$i] : "";
        $orderParams['extras']['events'][$i]['totalAmount'] = ($input['eventsTotalAmount'][$i]) ? (integer) $input['eventsTotalAmount'][$i] : 0;
        $orderParams['extras']['events'][$i]['productQuantity'] = ($input['eventsProductQuantity'][$i]) ? (integer) $input['eventsProductQuantity'][$i] : 0;
    }
}

// Request Body Rewriting
$orderParams = ($input['requestBody']) ? json_decode($input['requestBody'], true) : $orderParams;

// 1st Pay request
try {

    // Online Reserve API
    $response = $linePay->oneTimeKeysPay($orderParams);

    // Log
    saveLog('OneTimeKeysPay API', $response, true);

    // Check Reserve API result
    if (!$response->isSuccessful()) {
        die("<script>alert('ErrorCode {$response['returnCode']}: {$response['returnMessage']}');history.back();</script>");
    }

    // Detail v2 API for easily checking log
    $resForLog = $linePay->details([
        'transactionId' => $response["info"]["transactionId"],
    ], 'v2');
    // Log
    saveLog('Payment Details API', $resForLog); 

} catch(ConnectException $e) {
    
    // Log
    saveErrorLog('OneTimeKeysPay API', $linePay->getRequest(), true, $linePay->getLastStats());

    // 2st check requst
    try {
        // Use Order Check API to confirm the transaction
        $response = $linePay->ordersCheck($orderId);

    } catch (ConnectException $e) {

        // Log
        saveErrorLog('Payment Status Check API', $linePay->getRequest(), false, $linePay->getLastStats());
        die("<script>alert('APIs has timeout two times, please check orderId: {$orderId}');location.href='./';</script>");
    }
    
    // Log
    saveLog('Payment Status Check API', $response);
    
    // Check the transaction
    if (!$response->isSuccessful() || !isset($response["info"]) || $response["info"]['orderId'] != $orderId) {
        $_SESSION['linePayOrder']['isSuccessful'] = false;
        die("<script>alert('Payment Status Check Failed\\nErrorCode {$response['returnCode']}: {$response['returnMessage']}');history.back();</script>");
    }
}

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => (string) $response["info"]["transactionId"],
    'params' => $orderParams,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
$_SESSION['config'] = $input;

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;
$_SESSION['linePayOrder']['info'] = $response["info"];

// Redirect to LINE Pay payment URL 
header("Location: ./index.php?route=order");