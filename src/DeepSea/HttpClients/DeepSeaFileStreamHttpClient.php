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
        if ($this->client === null) {
            throw new DeepSeaException('Unable to Initialize File Stream', 1001);
        }

        $this->addRequestHeader('Connection', 'Close');
        $this->addRequestHeader('User-Agent', " FOpen", false);
    }

    /**
     * @param $url
     * @param array $parameter
     * @param string $method
     * @return DeepSeaHttpResponse
     */
    public function send($url, $parameter = array(), $method = HTTP::GET) {
        $this->client->setOpt('header', implode("\r\n", $this->formatRequestHeader()));
        if ($method !== HTTP::GET) {
            $this->addRequestHeader('Content-Type', TYPE::JSON);
            $this->client->setOpt('content', json_encode($parameter));
        }
        $this->client->open($url);
        $this->response = $this->client->exec();
        $result = $this->parseResponse();
        $this->client->close();
        return $result;
    }

    private function parseResponse() {
        $header = $this->response['header'];
        $matches = array();
        preg_match('#HTTP/\d+\.\d+ (\d+)#', $header[0], $matches);
        $this->responseCode = $matches[1];
        $this->parseResponseHeader(implode("\r\n", $header));
        return new DeepSeaHttpResponse($this->getResponseHeader(), $this->response['content']);
    }


}