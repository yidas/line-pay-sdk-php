<?php

namespace yidas\linePay;

use Exception;
use yidas\linePay\Response;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;

/**
 * LINE Pay Client
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 3.5.0
 */
class Client
{
    /**
     * LINE Pay base API host
     */
    const API_HOST = 'https://api-pay.line.me';

    /**
     * LINE Pay Sandbox base API host
     */
    const SANDBOX_API_HOST = 'https://sandbox-api-pay.line.me';

    /**
     * LINE Pay API URI list
     *
     * @var array
     */
    protected static $apiUris = [
        'request' => '/v3/payments/request',
        'confirm' => '/v3/payments/{transactionId}/confirm',
        'refund' => '/v3/payments/{transactionId}/refund',
        'details' => '/v3/payments',
        'check' => '/v3/payments/requests/{transactionId}/check',
        'authorizationsCapture' => '/v3/payments/authorizations/{transactionId}/capture',
        'authorizationsVoid' => '/v3/payments/authorizations/{transactionId}/void',
        'preapproved' => '/v3/payments/preapprovedPay/{regKey}/payment',
        'preapprovedCheck' => '/v3/payments/preapprovedPay/{regKey}/check',  
        'preapprovedExpire' => '/v3/payments/preapprovedPay/{regKey}/expire',
        'oneTimeKeysPay' => '/v2/payments/oneTimeKeys/pay',
        'ordersCheck' => '/v2/payments/orders/{orderId}/check',
        'ordersVoid' => '/v2/payments/orders/{orderId}/void',
        'ordersCapture' => '/v2/payments/orders/{orderId}/capture',
        'ordersRefund' => '/v2/payments/orders/{orderId}/refund',
        'authorizations' => '/v2/payments/authorizations',
    ];

    /**
     * HTTP Client
     *
     * @var GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * PSR-7 Request
     *
     * @var GuzzleHttp\Psr7\Request
     */
    protected $request;

    /**
     * Saved LINE Pay Channel Secret for v3 API Authentication
     *
     * @var string
     */
    protected $channelSecret;

    /**
     * Constructor
     *
     * @param string|array $optParams API Key or option parameters
     *  'channelId' => Your merchant X-LINE-ChannelId
     *  'channelSecret' => Your merchant X-LINE-ChannelSecret
     *  'isSandbox' => Sandbox mode
     * @return self
     */
    function __construct($optParams) 
    {
        // Assignment
        $channelId = isset($optParams['channelId']) ? $optParams['channelId'] : null;
        $channelSecret = isset($optParams['channelSecret']) ? $optParams['channelSecret'] : null;
        $merchantDeviceType = isset($optParams['merchantDeviceType']) ? $optParams['merchantDeviceType'] : null;
        $merchantDeviceProfileId = isset($optParams['merchantDeviceProfileId']) ? $optParams['merchantDeviceProfileId'] : null;
        $isSandbox = isset($optParams['isSandbox']) ? $optParams['isSandbox'] : false;

        // Check
        if (!$channelId || !$channelSecret) {
            throw new Exception("channelId/channelSecret are required", 400);
        }

        // Base URI
        $baseUri = ($isSandbox) ? self::SANDBOX_API_HOST : self::API_HOST;

        // Headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-LINE-ChannelId' => $channelId,
        ];
        // Save channel secret
        $this->channelSecret = (string) $channelSecret;
        // MerchantDeviceType
        if ($merchantDeviceType) {
            $headers['X-LINE-MerchantDeviceType'] = $merchantDeviceType;
        }
        // MerchantDeviceProfileId
        if ($merchantDeviceProfileId) {
            $headers['X-LINE-MerchantDeviceProfileId'] = $merchantDeviceProfileId;
        }

        // Load GuzzleHttp\Client
        $this->httpClient = new HttpClient([
            'base_uri' => $baseUri,
            // 'timeout'  => 6.0,
            'headers' => $headers,
            'http_errors' => false,
        ]);

        return $this;
    }

    /**
     * Get LINE Pay signature for authentication
     *
     * @param string $channelSecret
     * @param string $uri
     * @param string $queryOrBody
     * @param string $nonce
     * @return string
     */
    static public function getAuthSignature($channelSecret, $uri, $queryOrBody, $nonce)
    {
        $authMacText = $channelSecret . $uri . $queryOrBody . $nonce;
        return base64_encode(hash_hmac('sha256', $authMacText, $channelSecret, true));
    }

    /**
     * Get Request object
     *
     * @return GuzzleHttp\Psr7\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Client request handler with version
     *
     * @param string $var
     * @param string $method
     * @param string $uri
     * @param array $queryParams
     * @param array $bodyParams
     * @param array $options
     * @return yidas\linePay\Response
     */
    protected function requestHandler($version, $method, $uri, $queryParams=null, $bodyParams=null, $options=[])
    {
        // Headers
        $headers = [];
        // Query String
        $queryString = ($queryParams) ? http_build_query($queryParams) : null;
        $url = ($queryParams) ? "{$uri}?{$queryString}" : $uri ;
        // Body
        $body = ($bodyParams) ? json_encode($bodyParams) : '';

        // Guzzle on_stats
        $stats = null;
        $options['on_stats'] = function (\GuzzleHttp\TransferStats $transferStats) use (&$stats) {
            // Assign object
            $stats = $transferStats;
            $stats->responseTime = microtime(true);
            $stats->requestTime = $stats->responseTime - $stats->getTransferTime();
        };

        switch ($version) {
            case 'v2':
                // V2 API Authentication
                $headers['X-LINE-ChannelSecret'] = $this->channelSecret;
                break;
            
            case 'v3':
            default:
                // V3 API Authentication
                $authNonce = date('c') . uniqid('-'); // ISO 8601 date + UUID 1
                $authParams = ($method=='GET' && $queryParams) ? $queryString : (($bodyParams) ? $body : null);
                $headers['X-LINE-Authorization'] = self::getAuthSignature($this->channelSecret, $uri, $authParams, $authNonce);
                $headers['X-LINE-Authorization-Nonce'] = $authNonce;
                break;
        }

        // Send request with PSR-7 pattern
        $this->request = new Request($method, $url, $headers, $body);
        $this->request->timestamp = microtime(true);
        try {

            $response = $this->httpClient->send($this->request, $options);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {

            throw new \yidas\linePay\exception\ConnectException($e->getMessage(), $this->request);
        }

        return new Response($response, $stats);
    }

    /**
     * Get payment details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function details($queryParams)
    {
        return $this->requestHandler('v3', 'GET', self::$apiUris['details'], $queryParams, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Request payment
     *
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function request($bodyParams)
    {
        return $this->requestHandler('v3', 'POST', self::$apiUris['request'], null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Alias of Request payment
     *
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function reserve($bodyParams)
    {
        return $this->request($bodyParams);
    }

    /**
     * Payment confirm
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function confirm($transactionId, $bodyParams)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['confirm']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 40,
            ]);
    }

    /**
     * Refund confirm
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function refund($transactionId, $bodyParams=null)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['refund']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Check Payment Status API
     *
     * @param integer $transactionId
     * @return yidas\linePay\Response
     */
    public function check($transactionId)
    {
        return $this->requestHandler('v3', 'GET', str_replace('{transactionId}', $transactionId, self::$apiUris['check']), null, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Authorizations capture
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function authorizationsCapture($transactionId, $bodyParams)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsCapture']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 60,
            ]);
    }

    /**
     * Alias of Authorizations capture
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function capture($transactionId, $bodyParams)
    {
        return $this->authorizationsCapture($transactionId, $bodyParams);
    }

    /**
     * Void Authorization
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function authorizationsVoid($transactionId, $bodyParams=null)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsVoid']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Alias of Void Authorization
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function void($transactionId, $bodyParams=null)
    {
        return $this->authorizationsVoid($transactionId, $bodyParams);
    }

    /**
     * Void Authorization
     *
     * @param integer $regKey
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function preapproved($regKey, $bodyParams=null)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{regKey}', $regKey, self::$apiUris['preapproved']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 40,
            ]);
    }

    /**
     * Void Authorization
     *
     * @param integer $regKey
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function preapprovedCheck($regKey, $queryParams=null)
    {
        return $this->requestHandler('v3', 'GET', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedCheck']), null, $queryParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Void Authorization
     *
     * @param integer $regKey
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function preapprovedExpire($regKey, $bodyParams=null)
    {
        return $this->requestHandler('v3', 'POST', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedExpire']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * OneTimeKeys payment
     *
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function oneTimeKeysPay($bodyParams)
    {
        return $this->requestHandler('v2', 'POST', self::$apiUris['oneTimeKeysPay'], null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }
    
    /**
     * Payment Status Check
     *
     * @param string $orderId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function ordersCheck($orderId, $queryParams=null)
    {
        return $this->requestHandler('v2', 'GET', str_replace('{orderId}', $orderId, self::$apiUris['ordersCheck']), $queryParams, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Orders Void
     *
     * @param string $orderId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function ordersVoid($orderId, $bodyParams=null)
    {
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersVoid']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Orders Capture
     *
     * @param string $orderId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function ordersCapture($orderId, $bodyParams=null)
    {
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersCapture']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Orders Refund
     *
     * @param string $orderId
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function ordersRefund($orderId, $bodyParams=null)
    {
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersRefund']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Get Authorization details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function authorizations($queryParams)
    {
        return $this->requestHandler('v2', 'GET', self::$apiUris['authorizations'], $queryParams, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }
}
