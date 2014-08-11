<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/11/14
 * Time: 3:49 PM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;


use DeepSea\Entities\HTTP;
use DeepSea\Entities\TYPE;
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\HttpClients\Connections\DeepSeaFileStream;

class DeepSeaFileStreamHttpClient extends DeepSeaBaseHttpClient {

    /**
     * @var resource
     */
    protected $response = null;
    protected $client = null;

    public function __construct(DeepSeaFileStream $client = null) {
        parent::__construct();
        $this->client = $client ? : new DeepSeaFileStream();
        if ($this->curlClient === null) {
            throw DeepSeaException::create('Unable to Initialize File Stream', 1001);
        }

        $this->addRequestHeader('User-Agent', " FOpen", false);
    }

    /**
     * @param $url
     * @param array $parameter
     * @param string $method
     * @return DeepSeaHttpResponse
     */
    public function send($url, $parameter = array(), $method = HTTP::GET) {
        $this->client->setOpt('header', $this->formatRequestHeader());
        if ($method !== HTTP::GET) {
            $this->addRequestHeader('Content-Type', TYPE::JSON);
            $this->client->setOpt('content', json_encode($parameter));
        }
        $this->client->open($url);
        $this->response = $this->client->exec();
        return $this->parseResponse();
    }

    private function formatRequestHeader() {
        $result = array();
        foreach ($this->requestHeader as $key => $value) {
            array_push($result, sprintf('%s: %s', $key, $value));
        }
        sort($result);
        return implode("\r\n", $result);
    }

    private function parseResponse() {
        if ($this->response) {
            // TODO: Change this to actual response code!
            $this->responseCode = 200;
            $meta_data = stream_get_meta_data($this->response);
            $this->parseResponseHeader(implode("\r\n", $meta_data['wrapper_data']));
            $content = stream_get_contents($this->response);
        }
        return new DeepSeaHttpResponse($this->getResponseHeader(), $content);
    }


}