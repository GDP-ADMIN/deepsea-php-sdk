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

} 