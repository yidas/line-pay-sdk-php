<?php

namespace yidas\linePay;

use Exception;
use yidas\linePay\Response;
use GuzzleHttp\Client as HttpClient;

/**
 * LINE Pay Client
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 1.0.0
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
        'details' => '/v2/payments',
        'reserve' => '/v2/payments/request',
        'confirm' => '/v2/payments/{transactionId}/confirm',
        'refund' => '/v2/payments/{transactionId}/refund',
        'authorizations' => '/v2/payments/authorizations',
        'authorizationsCapture' => '/v2/payments/authorizations/{transactionId}/capture',
        'authorizationsVoid' => '/v2/payments/authorizations/{transactionId}/void',
        'preapproved' => '/v2/payments/preapprovedPay/{regKey}/payment',
        'preapprovedCheck' => '/v2/payments/preapprovedPay/{regKey}/check',  
        'preapprovedExpire' => '/v2/payments/preapprovedPay/{regKey}/expire',
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
            'X-LINE-ChannelId' => $channelId,
            'X-LINE-ChannelSecret' => $channelSecret,
        ];
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
     * Get payment details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function details($queryParams)
    {
        $response = $this->httpClient->request('GET', self::$apiUris['details'], ['query' => $queryParams]);

        return new Response($response);
    }

    /**
     * Reserve payment
     *
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function reserve($optParams)
    {
        $response = $this->httpClient->request('POST', self::$apiUris['reserve'], ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Payment confirm
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function confirm($transactionId, $optParams)
    {
        $response = $this->httpClient->request('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['confirm']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Refund confirm
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function refund($transactionId, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['refund']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Get Authorization details
     *
     * @param array $queryParams
     * @return yidas\linePay\Response
     */
    public function authorizations($queryParams)
    {
        $response = $this->httpClient->request('GET', self::$apiUris['authorizations'], ['query' => $queryParams]);

        return new Response($response);
    }

    /**
     * Authorizations capture
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function authorizationsCapture($transactionId, $optParams)
    {
        $response = $this->httpClient->request('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsCapture']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Alias of Authorizations capture
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function capture($transactionId, $optParams)
    {
        return $this->authorizationsCapture($transactionId, $optParams);
    }

    /**
     * Void Authorization
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function authorizationsVoid($transactionId, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsVoid']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Alias of Void Authorization
     *
     * @param integer $transactionId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function void($transactionId, $optParams=null)
    {
        return $this->authorizationsVoid($transactionId, $optParams);
    }

    /**
     * Void Authorization
     *
     * @param integer $regKey
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function preapproved($regKey, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{regKey}', $regKey, self::$apiUris['preapproved']), ['json' => $optParams]);

        return new Response($response);
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
        $response = $this->httpClient->request('GET', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedCheck']), ['query' => $queryParams]);

        return new Response($response);
    }

    /**
     * Void Authorization
     *
     * @param integer $regKey
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function preapprovedExpire($regKey, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedExpire']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * OneTimeKeys payment
     *
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function oneTimeKeysPay($optParams)
    {
        $response = $this->httpClient->request('POST', self::$apiUris['oneTimeKeysPay'], ['json' => $optParams]);

        return new Response($response);
    }
    
    /**
     * Payment Status Check
     *
     * @param string $orderId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function ordersCheck($orderId, $queryParams=null)
    {
        $response = $this->httpClient->request('GET', str_replace('{orderId}', $orderId, self::$apiUris['ordersCheck']), ['query' => $queryParams]);

        return new Response($response);
    }

    /**
     * Orders Void
     *
     * @param string $orderId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function ordersVoid($orderId, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersVoid']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Orders Capture
     *
     * @param string $orderId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function ordersCapture($orderId, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersCapture']), ['json' => $optParams]);

        return new Response($response);
    }

    /**
     * Orders Refund
     *
     * @param string $orderId
     * @param array $optParams
     * @return yidas\linePay\Response
     */
    public function ordersRefund($orderId, $optParams=null)
    {
        $response = $this->httpClient->request('POST', str_replace('{orderId}', $orderId, self::$apiUris['ordersRefund']), ['json' => $optParams]);

        return new Response($response);
    }
}
