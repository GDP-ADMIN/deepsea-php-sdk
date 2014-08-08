<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:12 AM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;

use DeepSea\Entities\HTTP;

interface DeepSeaHttpClientInterface {

    public function addRequestHeader($key, $value);

    /**
     * @return array
     */
    public function getResponseHeader();

    /**
     * @param $url
     * @param array $parameter
     * @param string $method
     * @return DeepSeaHttpResponse
     */
    public function send($url, $parameter = array(), $method = HTTP::GET);

} 