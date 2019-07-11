<?php

require __DIR__ . '/_config.php';

// Get saved config
$config = $_SESSION['config'];
// Check session
if (!$config) {
    die("<script>alert('Session expired, please resend.');history.back();</script>");
}
// Create LINE Pay client
$linePay = new \yidas\linePay\Client([
    'channelId' => $config['channelId'],
    'channelSecret' => $config['channelSecret'],
    'isSandbox' => ($config['isSandbox']) ? true : false, 
]);

// Successful page URL
$successUrl = './index.php?route=order';
// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];
// Get the order from session
$order = $_SESSION['linePayOrder'];

// Check Payment Status API
$response = $linePay->check($transactionId);

// Log
saveLog('Check Payment Status API', [], null, $response->toArray(), null);

// Check result
if (!$response->isSuccessful()) {
    die("<script>alert('Refund Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');history.back();</script>");
}

die("<script>alert('Result:\\nCode: {$response['returnCode']}\\nMessage: {$response['returnMessage']}');history.back();</script>");
var_dump($response["info"]);