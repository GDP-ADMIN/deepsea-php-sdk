<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:47 AM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;

use DeepSea\Entities\HTTP;
use DeepSea\Entities\TYPE;
use DeepSea\Exceptions\DeepSeaException;

class DeepSeaCurlHttpClient extends DeepSeaBaseHttpClient {
    /**
     * @var DeepSeaCurl
     */
    private $curlClient = null;

    /**
     * Create Http Client with Curl
     *
     * @param DeepSeaCurl $curlClient
     * @throws DeepSeaException
     */
    public function __construct(DeepSeaCurl $curlClient = null) {
        parent::__construct();
        $this->curlClient = $curlClient ? : new DeepSeaCurl();
        if ($this->curlClient === null) {
            throw DeepSeaException::create('Unable to Initialize Curl', 1001);
        }
        $curlInfo = $this->curlClient->getVersion();

        $this->addRequestHeader('Accept', TYPE::JSON);
        $this->addRequestHeader('Connection', 'Keep-Alive');
        $this->addRequestHeader('User-Agent', sprintf(" Curl/%s", $curlInfo['features']), false);
    }

    /**
     * Send Request to URL
     *
     * @param $url
     * @param array $parameter
     * @param string $method
     * @return DeepSeaHttpResponse
     * @throws \DeepSea\Exceptions\DeepSeaException
     */
    public function send($url, $parameter = array(), $method = HTTP::GET) {
        $this->open($url, $parameter, $method);
        $this->sendCurl();
        if ($this->error !== null) {
            // Something is Wrong With The SSL
            if ($this->error->getCode() == CURLE_SSL_CACERT || $this->error->getCode() == CURLE_SSL_CACERT_BADFILE) {
                $this->error = null;
                $this->attachCertificate();
                $this->sendCurl();
            }
        }
        if ($this->error !== null) { throw $this->error; }
        $result = $this->parseResponse();
        $this->close();

        return $result;
    }

    private function open($url, $parameter = array(), $method = HTTP::GET) {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
        );
        if ($method !== HTTP::GET) {
            $this->addRequestHeader('Content-Type', TYPE::JSON);
            $options[CURLOPT_POSTFIELDS] = json_encode($parameter);
        }
        $options[CURLOPT_HTTPHEADER] = $this->getResponseHeader();
        $this->curlClient->setOptArray($options);
    }

    private function sendCurl() {
        $this->response = $this->curlClient->exec();
        $this->responseCode = $this->curlClient->getinfo(CURLINFO_HTTP_CODE);
        if ($this->curlClient->errno() !== CURLE_OK) {
            $this->error = DeepSeaException::create($this->curlClient->error(), $this->curlClient->errno());
        }
    }

    private function attachCertificate() {
        $certificate = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'deepsea.crt';
        if (!file_exists($certificate)) { throw new DeepSeaException('Unable to find Certificate', 1002); }
        $this->curlClient->setOptArray(CURLOPT_CAINFO, $certificate);
    }

    private function close() {
        $this->curlClient->close();
    }

    private function parseHeader($header) {
        $http_response_header = explode("\r\n", $header);
        $result = array();
        $result["Status"] = $http_response_header[0];

        $matches = array();
        preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
        $result["Code"] = $matches[1];

        for ($i = 1; $i < sizeof($http_response_header); $i++) {
            if (strlen($http_response_header[$i]) > 0 && strpos($http_response_header[$i], ':') > 0) {
                $index = strpos($http_response_header[$i], ":");
                $result[trim(substr($http_response_header[$i], 0, $index))] = trim(substr($http_response_header[$i], $index + 1));
            }
        }
        return json_encode($result);
    }

    private function parseResponse() {
        $headerSize = $this->curlClient->getinfo(CURLINFO_HEADER_SIZE);
        $ver = $this->curlClient->getVersion();
        if ($ver['version_number'] < DeepSeaCurl::CURL_PROXY_QUIRK_VER) {
            if (preg_match('/Content-Length: (\d+)/', $this->response, $match)) {
                $headerSize = mb_strlen($this->response) - $match[1];
            } elseif (strpos($this->response, DeepSeaCurl::CONNECTION_ESTABLISHED) !== false) {
                $headerSize += mb_strlen(DeepSeaCurl::CONNECTION_ESTABLISHED);
            }
        }

        $header = trim(mb_substr($this->response, 0, $headerSize));
        $content = trim(mb_substr($this->response, $headerSize));

        $header = $this->parseHeader($header);
        $this->responseCode = 0;
        return new DeepSeaHttpResponse($header, $content);
    }

}