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
 * @param \yidas\linePay\Response $response
 * @param boolean $reset
 * @return void
 */
function saveLog($name, \yidas\linePay\Response $response, $reset=false)
{
    $stats = $response->getStats();
    $request = $stats->getRequest();
    // Rewind the stream
    $request->getBody()->rewind();

    // Content
    $requestContentArray = json_decode($request->getBody()->getContents());
    $responseContentArray = $response->toArray();
    
    // Log
    $logs = ($reset) ? [] : $_SESSION['logs'];
    $logs[] = [
        'name' => $name, 
        'datetime' => date("c"), 
        'uri' => urldecode($stats->getEffectiveUri()->__toString()),
        'method' => $request->getMethod(),
        'transferTime' => $stats->getTransferTime(),
        'request' => [
            'content' => ($requestContentArray) ? json_encode($requestContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($stats->requestTime),
        ],
        'response' => [
            'content' => ($responseContentArray) ? json_encode($responseContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($stats->responseTime),
        ],
    ];
    $_SESSION['logs'] = $logs;
}

/**
 * Save log into logs in session
 *
 * @param string $name
 * @param \Psr\Http\Message\RequestInterface $request
 * @param boolean $reset
 * @return void
 */
function saveErrorLog($name, \Psr\Http\Message\RequestInterface $request, $reset=false)
{    
    // Content
    $requestContentArray = json_decode($request->getBody());
    // Timestamp trick
    $request->timestamp = isset($request->timestamp) ? $request->timestamp : time();
    // Log
    $logs = ($reset) ? [] : $_SESSION['logs'];
    $logs[] = [
        'name' => $name, 
        'datetime' => date("c"), 
        'uri' => urldecode($request->getUri()),
        'method' => $request->getMethod(),
        'transferTime' => (float) (microtime(true) - $request->timestamp),
        'request' => [
            'content' => ($requestContentArray) ? json_encode($requestContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($request->timestamp),
        ],
        'response' => [
            'content' => '',
            'datetime' => 'Timeout',
        ],
    ];
    $_SESSION['logs'] = $logs;
}

/**
 * Merchant Helper
 */
class Merchant
{
    private static $configPath = __DIR__ . "/_merchants.json";

    /**
     * Get merchant list from config
     *
     * @return array
     */
    public static function getList()
    {
        if (file_exists(self::$configPath)) {
        
            $data = json_decode(file_get_contents(self::$configPath), true);

            // Check format
            foreach ((array)$data as $key => $each) {
                if (!isset($each['channelId']) || !isset($each['channelSecret'])) {
                    die("<strong>ERROR:</strong> Incorrect merchant config format - Each merchant must include `channelId` and `channelSecret`.<br>\n (" . self::$configPath . ")");
                }
            }

            return $data;
        }

        return null;
    }

    /**
     * Get a merchant data by key
     *
     * @param string $key
     * @return array
     */
    public static function getMerchant($key)
    {
        $merchants = self::getList();

        return isset($merchants[$key]) ? $merchants[$key] : null;
    }
}

/**
 * Print formatted date time
 *
 * @param float $timestamp
 * @return string
 */
function printDateTime($timestamp)
{
    return DateTime::createFromFormat('U.u', $timestamp)->setTimeZone(new DateTimeZone(date_default_timezone_get()))->format("Y-m-d H:i:s.u");
}