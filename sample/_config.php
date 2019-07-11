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
            foreach ($data as $key => $each) {
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
