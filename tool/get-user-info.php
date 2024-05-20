<?php

require __DIR__ . '/_config.php';

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
]);

// Use Order Check API to confirm the transaction
$response = $linePay->getUserInfo($input['transactionIdForUserInfo']);

// Log
saveLog('Get User Info API', $response, true);

// Save error info if confirm fails
if (!$response->isSuccessful() || !isset($response["info"])) {
    die("<script>alert('Details Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');history.back();</script>");
}

// Save input for next process and next form
$_SESSION['config'] = $input;

$infoString = json_encode($response["info"]);
die("<script>alert('Result:\\nCode: {$response['returnCode']}\\nMessage: {$response['returnMessage']}\\nInfo: {$infoString}');history.back();</script>");
