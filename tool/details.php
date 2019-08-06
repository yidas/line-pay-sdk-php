<?php

require __DIR__ . '/_config.php';

$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
// Custom merchant only for security
if (isset($input['merchant'])) {
    die("<script>alert('Custom merchant input only!');history.back();</script>");
}

// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $input['channelId'],
    'channelSecret' => $input['channelSecret'],
    'isSandbox' => $input['isSandbox'], 
]);

// Use Order Check API to confirm the transaction
$response = $linePay->details([
    'transactionId' => [$input['transactionId']],
]);

// Log
saveLog('Payment Details API', [], null, $response->toArray(), null);

// Save error info if confirm fails
if (!$response->isSuccessful() || !isset($response["info"]) || $response["info"][0]['transactionId'] != $input['transactionId']) {
    die("<script>alert('Details Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');history.back();</script>");
}

// Save the order info to session for confirm
$_SESSION['linePayOrder'] = [
    'transactionId' => $input['transactionId'],
    'params' => $response["info"][0],
    'isSandbox' => $input['isSandbox'], 
];

// PayInfo sum up
$amount = 0;
foreach ($response["info"][0]['payInfo'] as $key => $payInfo) {
    if ($payInfo['method'] != 'DISCOUNT') {
        $amount += $payInfo['amount'];
    }
}
$_SESSION['linePayOrder']['params']['amount'] = $amount;
$_SESSION['linePayOrder']['info'] = $response["info"][0];

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;

// Save input for next process and next form
$_SESSION['config'] = $input;

// Redirect to LINE Pay payment URL 
header("Location: ./index.php?route=order");