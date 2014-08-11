<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 1:01 PM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;

use DeepSea\Entities\HTTP;

class DeepSeaRequest {

    /**
     * @var DeepSeaHttpClientInterface
     */
    private static $httpClient = null;

    /**
     * @var array
     */
    private $headers = array();

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
        if (static::$httpClient) {
            return static::$httpClient;
        }
        return (function_exists('curl_init') && is_callable('curl_init')) ? new DeepSeaCurlHttpClient() : new DeepSeaCurlHttpClient();
    }

    public function setRequestHeaders($headers = array()) {
        $this->headers = $headers;
    }

    public function __construct(DeepSeaHttpClientInterface $httpClient = null) {
        static::setHttpClient($httpClient ? : static::getHttpClient());
    }

    public function send($url, $params = array(), $method = HTTP::GET) {
        $httpClient = static::getHttpClient();

        if ($method === HTTP::GET || $method === HTTP::DELETE) {
            $url = $this->buildQueryString($url, $params);
            $params = array();
        }
        foreach ($this->headers as $key => $value) {
            $httpClient->addRequestHeader($key, $value);
        }

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