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

// Online Reserve API
$response = $linePay->preapprovedCheck($input['regKey'], $_GET);

// Log
saveLog('Preapproved Check API', $response, true);

die("<script>alert('Result:\\nCode: {$response['returnCode']}\\nMessage: {$response['returnMessage']}');history.back();</script>");