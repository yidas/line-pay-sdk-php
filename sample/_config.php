<?php
// Debug
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Session
session_start();

// Vendor
require __DIR__ . '/../vendor/autoload.php';

/**
 * Save log into logs in session
 *
 * @param string $name
 * @param array $requestBody
 * @param string $requestTime
 * @param array $responseBody
 * @param string $responseTime
 * @param boolean $reset
 * @return void
 */
function saveLog($name, array $requestBody=null, $requestTime=null, array $responseBody, $responseTime=null, $reset=false)
{
    // Log
    $logs = ($reset) ? [] : $_SESSION['logs'];
    $logs[] = [
        'name' => $name, 
        'datetime' => date("c"), 
        'request' => [
            'content' => ($requestBody) ? json_encode($requestBody, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => $requestTime,
        ],
        'response' => [
            'content' => ($responseBody) ? json_encode($responseBody, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => $responseTime,
        ],
    ];
    $_SESSION['logs'] = $logs;
}