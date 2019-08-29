<?php

require __DIR__ . '/_config.php';

$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
unset($input['preapproved']);
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
]);

// Create an order based on Reserve API parameters
$orderParams = [
    "productName" => $input['productName'],
    "amount" => (integer) $input['amount'],
    "currency" => $input['currency'],
    "orderId" => "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3),
];

// Capture: false
if (isset($input['captureFalse'])) {
    $orderParams['capture'] = false;
}

// Online Reserve API
$response = $linePay->preapproved($input['regKey'], $orderParams);

// Log
saveLog('Preapproved Pay API', $response, true);

// Check Reserve API result
if (!$response->isSuccessful()) {
    die("<script>alert('ErrorCode {$response['returnCode']}: " . addslashes($response['returnMessage']) . "');history.back();</script>");
}

$transactionId = (string) $response["info"]["transactionId"];

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => $transactionId,
    'params' => $orderParams,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
$_SESSION['config'] = $input;

// Use Details API to confirm the transaction (Details API verification is more stable then Confirm API)
$response = $linePay->details([
    'transactionId' => $transactionId,
]);

// Log
saveLog('Payment Details API', $response);

// Save error info if confirm fails
if (!$response->isSuccessful()) {
    $_SESSION['linePayOrder']['confirmCode'] = $response['returnCode'];
    $_SESSION['linePayOrder']['confirmMessage'] = $response['returnMessage'];
}

// Check the transaction
if (!isset($response["info"]) || $response["info"][0]['transactionId'] != $transactionId) {
    $_SESSION['linePayOrder']['isSuccessful'] = false;
    die("<script>alert('Details Failed\\nErrorCode: {$_SESSION['linePayOrder']['confirmCode']}\\nErrorMessage: {$_SESSION['linePayOrder']['confirmMessage']}');history.back();</script>");
}

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;
$_SESSION['linePayOrder']['info'] = $response["info"][0];

// Redirect to successful page
header("Location: ./index.php?route=order");