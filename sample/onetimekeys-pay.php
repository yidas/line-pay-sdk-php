<?php

require __DIR__ . '/_config.php';

// Get Base URL path without filename
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".dirname($_SERVER['PHP_SELF']);
$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
$input['otk'] = trim(str_replace(' ', '', $input['otk']));

// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
]);

// Create an order based on Reserve API parameters
$orderId = "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3);
$orderParams = [
    'productName' => $input['productName'],
    'amount' => (integer) $input['amount'],
    'currency' => $input['currency'],
	'orderId' => $orderId,
	'oneTimeKey' => $input['otk'],
];

// Online Reserve API
$response = $linePay->oneTimeKeysPay($orderParams);

// Log
saveLog('OneTimeKeysPay API', $orderParams, null, $response->toArray(), null, true);

// Check Reserve API result
if (!$response->isSuccessful()) {
    die("<script>alert('ErrorCode {$response['returnCode']}: {$response['returnMessage']}');history.back();</script>");
}

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => (string) $response["info"]["transactionId"],
    'params' => $orderParams,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
$_SESSION['config'] = $input;

// Use Order Check API to confirm the transaction
$response = $linePay->ordersCheck($orderId);

// Log
saveLog('Payment Status Check API', [], null, $response->toArray(), null);

// Check the transaction
if (!isset($response["info"]) || $response["info"]['orderId'] != $orderId) {
    $_SESSION['linePayOrder']['isSuccessful'] = false;
    die("<script>alert('Payment Status Check Failed\\nErrorCode {$response['returnCode']}: {$response['returnMessage']}');history.back();</script>");
}

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;

// Redirect to LINE Pay payment URL 
header("Location: ./index.php?route=order");