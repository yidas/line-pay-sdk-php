<?php

use yidas\linePay\Client;
use yidas\linePay\exception\ConnectException;

require __DIR__ . '/_config.php';

$input = $_GET;
if (!$input) {
    die("<script>alert('Query invalid');location.href='./index.php';</script>");
}

$merchant = Merchant::getMerchant($input['merchant']);

// Create LINE Pay client
$linePay = new Client([
    'channelId' => $merchant['channelId'],
    'channelSecret' => $merchant['channelSecret'],
    'isSandbox' => false, 
]);

// Successful page URL
$successUrl = './index.php?route=order';
// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];

// var_dump($order);exit;
// Online Confirm API
$bodyParams = [
    'amount' => (integer) $input['amount'],
    'currency' => $input['currency'],
];

// 1st confirm request
try {
    
    $response = $linePay->confirm($transactionId, $bodyParams);

    // Save error info if confirm fails
    if (!$response->isSuccessful()) {
    }

} catch (ConnectException $e) {
    
    // 2st check Details request
    try {

        // Retry check by using Details API to confirm the transaction
        $response = $linePay->details([
            'transactionId' => $order['transactionId'],
        ]);

    } catch (ConnectException $e) {
        
        saveErrorLog('Details API', $linePay->getRequest());
        die("<script>alert('APIs has timeout two times, please check transactionId: {$order['transactionId']}');location.href='./';</script>");
    }

    // Check result
    if (!$response->isSuccessful() || !isset($response["info"]) || $response["info"][0]['transactionId'] != $transactionId) {
    }
}