<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 2:51 PM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;

use DeepSea\DeepSea;
use DeepSea\Entities\HTTP;
use DeepSea\Entities\TYPE;
use DeepSea\Exceptions\DeepSeaException;

abstract class DeepSeaBaseHttpClient implements DeepSeaHttpClientInterface {
    /**
     * @var array
     */
    protected $requestHeader = array();

    /**
     * @var array
     */
    protected $responseHeader = array();

    /**
     * @var int
     */
    protected $responseCode = 0;

    /**
     * @var DeepSeaException
     */
    protected $error = null;

    /**
     * @var string
     */
    protected $response = null;

    public function __construct() {
        $this->addRequestHeader('User-Agent', sprintf("DeepSea/%s (%s; %s; %s) PHP/%s",
                DeepSea::SDK_VERSION,
                php_uname('s'), php_uname('r'), php_uname('m'),
                phpversion())
        );
        $this->addRequestHeader('Accept', TYPE::JSON);
        $this->addRequestHeader('Connection', 'Keep-Alive');
    }


    /**
     * Add request header
     *
     * @param $key
     * @param $value
     * @param $replace
     */
    public function addRequestHeader($key, $value, $replace = true) {
        if (!$replace && !isset($this->requestHeader[$key])) {
            $this->addRequestHeader($key, $value, true);
        } else {
            $this->requestHeader[$key] = $replace ? $value : $this->requestHeader[$key] . ',' . $value;
        }
        ksort($this->requestHeader);
    }

    /**
     * @return array
     */
    public function getResponseHeader() {
        ksort($this->responseHeader);
        return $this->responseHeader;
    }

    /**
     * @return int
     */
    public function getResponseCode() {
        return $this->responseCode;
    }

    /**
     * @param $url
     * @param array $parameter
     * @param string $method
     * @return DeepSeaHttpResponse
     */
    abstract public function send($url, $parameter = array(), $method = HTTP::GET);

    protected function formatRequestHeader() {
        $result = array();
        foreach ($this->requestHeader as $key => $value) {
            array_push($result, sprintf('%s: %s', $key, $value));
        }
        sort($result);
        return $result;
    }

    protected function parseResponseHeader($header) {
        $http_response_header = explode("\r\n", $header);
        $result = array();
        $result["Status"] = $http_response_header[0];
        $result["Code"] = intval($this->getResponseCode());

        $headerSize = sizeof($http_response_header);
        for ($i = 1; $i < $headerSize; $i++) {
            if (strlen($http_response_header[$i]) > 0 && strpos($http_response_header[$i], ':') > 0) {
                $index = strpos($http_response_header[$i], ":");
                $result[trim(substr($http_response_header[$i], 0, $index))] = trim(substr($http_response_header[$i], $index + 1));
            }
        }
        $this->responseHeader = $result;
    }

} 