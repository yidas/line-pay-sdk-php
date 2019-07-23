<?php

namespace yidas\linePay;

use Exception;
use yidas\linePay\Response;
use GuzzleHttp\Client as HttpClient;

/**
 * LINE Pay Client
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 3.0.1
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
        'authorizations' => '/v3/payments/authorizations',
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
    ];

    /**
     * HTTP Client
     *
     * @var GuzzleHttp\Client
     */
    protected $httpClient;

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
     *  'key' => Google API Key
     *  'clientID' => Google clientID
     *  'clientSecret' => Google clientSecret
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
        ]);

        return $this;
    }

    /**
     * Client request handler with version
     *
     * @param string $var
     * @param string $method
     * @param string $uri
     * @param array $queryParams
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    protected function requestHandler($version, $method, $uri, $queryParams=null, $bodyParams=null)
    {
        // Request options
        $options = [];
        if ($queryParams) {
            $options['query'] = $queryParams;
        }
        if ($bodyParams) {
            $options['body'] = json_encode($bodyParams);
        }

        switch ($version) {
            case 'v2':
                // V2 API Authentication
                $options['headers']['X-LINE-ChannelSecret'] = $this->channelSecret;
                $response = $this->httpClient->request($method, $uri, $options);
                break;
            
            case 'v3':
            default:
                // V3 API Authentication
                $authNonce = date('c'); // ISO 8601 date
                $authParams = ($method=='GET' && $queryParams) ? http_build_query($queryParams) : (($bodyParams) ? $options['body'] : null);
                $authMacText = $this->channelSecret . $uri . $authParams . $authNonce;
                $options['headers']['X-LINE-Authorization'] = base64_encode(hash_hmac('sha256', $authMacText, $this->channelSecret, true));
                $options['headers']['X-LINE-Authorization-Nonce'] = $authNonce;
                $response = $this->httpClient->request($method, $uri, $options);
                break;
        }

        return new Response($response);
    }

    /**
     * Get payment details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function details($queryParams)
    {
        return $this->requestHandler('v3', 'GET', self::$apiUris['details'], $queryParams);
    }

    /**
     * Request payment
     *
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function request($bodyParams)
    {
        return $this->requestHandler('v3', 'POST', self::$apiUris['request'], null, $bodyParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['confirm']), null, $bodyParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['refund']), null, $bodyParams);
    }

    /**
     * Check Payment Status API
     *
     * @param integer $transactionId
     * @return yidas\linePay\Response
     */
    public function check($transactionId)
    {
        return $this->requestHandler('v3', 'GET', str_replace('{transactionId}', $transactionId, self::$apiUris['check']));
    }

    /**
     * Get Authorization details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function authorizations($queryParams)
    {
        return $this->requestHandler('v3', 'GET', self::$apiUris['authorizations'], $queryParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsCapture']), null, $bodyParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsVoid']), null, $bodyParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{regKey}', $regKey, self::$apiUris['preapproved']), null, $bodyParams);
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
        return $this->requestHandler('v3', 'GET', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedCheck']), $queryParams);
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
        return $this->requestHandler('v3', 'POST', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedExpire']), null, $bodyParams);
    }

    /**
     * OneTimeKeys payment
     *
     * @param array $bodyParams
     * @return yidas\linePay\Response
     */
    public function oneTimeKeysPay($bodyParams)
    {
        return $this->requestHandler('v2', 'POST', self::$apiUris['oneTimeKeysPay'], null, $bodyParams);
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
        return $this->requestHandler('v2', 'GET', str_replace('{orderId}', $orderId, self::$apiUris['ordersCheck']), $queryParams);
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
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersVoid']), null, $bodyParams);
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
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersCapture']), null, $bodyParams);
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
        return $this->requestHandler('v2', 'POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersRefund']), null, $bodyParams);
    }
}
