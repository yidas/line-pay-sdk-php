<?php

use yidas\linePay\Client;
use yidas\linePay\exception\ConnectException;

require __DIR__ . '/_config.php';

// Get saved config
$config = isset($_SESSION['config']) ? $_SESSION['config'] : null;
if (!$config) {
    die("<script>alert('Session invalid');location.href='./index.php';</script>");
}
// Create LINE Pay client
$linePay = new Client([
    'channelId' => $config['channelId'],
    'channelSecret' => $config['channelSecret'],
    'isSandbox' => ($config['isSandbox']) ? true : false, 
    'merchantDeviceProfileId' => $config['merchantDeviceProfileId'],
]);

// Successful page URL
$successUrl = './index.php?route=order';
// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];
// Get the order from session
$order = $_SESSION['linePayOrder'];

// Check transactionId (Optional)
if ($order['transactionId'] != $transactionId) {
    die("<script>alert('TransactionId doesn\'t match');location.href='./index.php';</script>");
}
// var_dump($order);exit;
// Online Confirm API
$bodyParams = [
    'amount' => (integer) $order['params']['amount'],
    'currency' => $order['params']['currency'],
];

// 1st confirm request
try {
    
    $response = $linePay->confirm($order['transactionId'], $bodyParams);

    // Log
    saveLog('Confirm API', $response);

    // Save error info if confirm fails
    if (!$response->isSuccessful()) {
        $_SESSION['linePayOrder']['isSuccessful'] = false;
        $_SESSION['linePayOrder']['confirmCode'] = $response['returnCode'];
        $_SESSION['linePayOrder']['confirmMessage'] = $response['returnMessage'];
        die("<script>alert('Confirm Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');location.href='{$successUrl}';</script>");
    }

    // Pre-approved 
    if (isset($config['preapproved'])) {
        $regKey = isset($response['info']['regKey']) ? $response['info']['regKey'] : null;
        if (!$regKey) {
            die("<script>alert('`regKey` not found!;location.href='{$successUrl}';</script>");
        }
        $_SESSION['config']['regKey'] = $regKey;
    }

} catch (ConnectException $e) {
    
    // Log
    saveErrorLog('Confirm API', $linePay->getRequest(), false, $linePay->getLastStats());

    // 2st check Details request
    try {

        // Retry check by using Details API to confirm the transaction
        $response = $linePay->details([
            'transactionId' => $order['transactionId'],
        ]);

    } catch (ConnectException $e) {
        
        saveErrorLog('Details API', $linePay->getRequest(), false, $linePay->getLastStats());
        die("<script>alert('APIs has timeout two times, please check transactionId: {$order['transactionId']}');location.href='./';</script>");
    }
    
    // Log
    saveLog('Details API', $response);

    // Check result
    if (!$response->isSuccessful() || !isset($response["info"]) || $response["info"][0]['transactionId'] != $transactionId) {
        $_SESSION['linePayOrder']['isSuccessful'] = false;
        $_SESSION['linePayOrder']['confirmCode'] = $response['returnCode'];
        $_SESSION['linePayOrder']['confirmMessage'] = $response['returnMessage'];
        die("<script>alert('Details Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');location.href='{$successUrl}';</script>");
    }
}

// Code for saving the successful order into your application database...
$_SESSION['linePayOrder']['isSuccessful'] = true;
$_SESSION['linePayOrder']['info'] = $response["info"];

// Redirect to successful page
header("Location: {$successUrl}");