<?php

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
$linePay = new \yidas\linePay\Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
    'merchantDeviceProfileId' => $input['merchantDeviceProfileId'],
]);

// Create an order based on Reserve API parameters
$orderId = ($input['orderId']) ? $input['orderId'] : "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3);
$orderParams = [
    'productName' => $input['productName'],
    'amount' => (integer) $input['amount'],
    'currency' => $input['currency'],
	'orderId' => $orderId,
	'oneTimeKey' => $input['otk'],
];

// Capture: false
if (isset($input['captureFalse'])) {
    $orderParams['capture'] = false;
}
// BrachName (Refund API doesn't support)
if ($input['branchName']) {
    $orderParams['extras']['branchName'] = $input['branchName'];
}
// PromotionRestriction
if ($input['useLimit'] || $input['rewardLimit']) {
    $orderParams['extras']['promotionRestriction']['useLimit'] = ($input['useLimit']) ? $input['useLimit'] : 0;
    $orderParams['extras']['promotionRestriction']['rewardLimit'] = ($input['rewardLimit']) ? $input['rewardLimit'] : 0;
}

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
$_SESSION['linePayOrder']['info'] = $response["info"];

// Redirect to LINE Pay payment URL 
header("Location: ./index.php?route=order");