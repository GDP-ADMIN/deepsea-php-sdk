<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 1:01 PM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;

use Deepsea\Deepsea;
use DeepSea\Entities\HTTP;
use DeepSea\Entities\DeepSeaSession;

class DeepSeaRequest {

    /**
     * @var DeepSeaHttpClientInterface
     */
    private static $httpClient;

    /**
     * @var DeepSeaSession
     */
    private $session;

    /**
     * @param \DeepSea\HttpClients\DeepSeaHttpClientInterface $httpClient
     */
    protected static function setHttpClient($httpClient) {
        static::$httpClient = $httpClient;
    }

    /**
     * @return \DeepSea\HttpClients\DeepSeaHttpClientInterface
     */
    public static function getHttpClient() {
        if (static::$httpClientHandler) {
            return static::$httpClientHandler;
        }
        return (function_exists('curl_init') && is_callable('curl_init')) ? new DeepSeaCurlHttpClient() : new DeepSeaCurlHttpClient();
    }

    public function __construct(DeepSeaSession $session, DeepSeaHttpClientInterface $httpClient = null) {
        static::setHttpClient(($httpClient) ? : static::getHttpClient());
        $this->session = $session;
    }

    public function send($url, $params = array(), $method = HTTP::GET) {
        $httpClient = static::getHttpClient();

        if ($method === HTTP::GET || $method === HTTP::DELETE) {
            $url = $this->buildQueryString($url, $params);
            $params = array();
        }
        $httpClient->addRequestHeader('Authorization', sprintf('Bearer %s', $this->session->getAccessToken()));
        $httpClient->addRequestHeader('User-Agent', sprintf("DeepSea/%s (%s; %s; %s) PHP/%s",
                DeepSea::SDK_VERSION,
                php_uname('s'), php_uname('r'), php_uname('m'),
                phpversion())
        );

        return $httpClient->send($url, $params, $method);
    }

    private function buildQueryString($url, $params = array()) {
        if (empty($params) || !$params) { return $url; }

        // Retain original query string
        if (strpos($url, '?') !== false) {
            list($url, $queryString) = explode('?', $url, 2);
            parse_str($queryString, $queryArray);
            $params = array_merge($params, $queryArray);
        }

        return $url . '?' . http_build_query($params, null, '&');
    }


} 