<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/19/14
 * Time: 9:30 AM
 * GDP Venture © 2014
 */

namespace Test\HttpClients;

use DeepSea\Entities\HTTP;
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\HttpClients\DeepSeaCurlHttpClient;
use DeepSea\Test\TestCase;
use Mockery;
use Mockery\MockInterface;

class DeepSeaCurlHttpClientTest extends TestCase {

    /* @var MockInterface $curl */
    protected $curl;

    public function setUp() {
        parent::setUp();
        $this->curl = Mockery::mock('DeepSea\HttpClients\Connections\DeepSeaCurl');
    }

    public function tearDown() {
        Mockery::close();
        new DeepSeaCurlHttpClient();
        parent::tearDown();
    }

    /**
     * @dataProvider urlAndMethodProvider
     */
    public function testSendWithMethod($url, $method, $data) {
        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // error
        $this->curl->shouldReceive('error')->never()->andReturn('');

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once()->andReturnUsing(function ($arg) use ($url, $method, $data) {
            $this->assertEquals($url, $arg[CURLOPT_URL]);
            $this->assertEquals($method, $arg[CURLOPT_CUSTOMREQUEST]);
            if ($method !== 'GET') { // Not GET must contain body
                $this->assertEquals(json_encode($data), $arg[CURLOPT_POSTFIELDS]);
            }
        });

        // exec
        $this->curl->shouldReceive('exec')->once();

        // getinfo
        $this->curl->shouldReceive('getinfo')->times(2)->andReturnUsing(function ($arg) {
            return $arg == CURLINFO_HTTP_CODE ? 200 : 0;
        });

        // close
        $this->curl->shouldReceive('close')->once();

        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $httpClient->send($url, $data, $method);
    }

    public function urlAndMethodProvider() {
        return array(
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::GET, array()),
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::POST, array('data' => uniqid('DATA_POST_'))),
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::PUT, array('data' => uniqid('DATA_PUT_'))),
        );
    }

    public function testSSLCaCertFail() {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;

        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->times(2)->andReturn(CURLE_SSL_CACERT);
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // error
        $this->curl->shouldReceive('error')->once()->andReturnUsing(function () {
            return new DeepSeaException('Error SSL', CURLE_SSL_CACERT);
        });

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once();

        $this->curl->shouldReceive('setOpt')->once();

        // exec
        $this->curl->shouldReceive('exec')->twice();

        // getinfo
        $this->curl->shouldReceive('getinfo')->times(3)->andReturnUsing(function ($arg) {
            return $arg == CURLINFO_HTTP_CODE ? 200 : 0;
        });

        // close
        $this->curl->shouldReceive('close')->once();

        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $httpClient->send($url, $data, $method);
    }

    public function testFailToSend() {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;

        // getVersion
        $this->curl->shouldReceive('getVersion')->once()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->twice()->andReturn(CURLE_COULDNT_CONNECT);

        // error
        $this->curl->shouldReceive('error')->once()->andReturnUsing(function () {
            return new DeepSeaException('Error Cannot Connect', CURLE_COULDNT_CONNECT);
        });

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once();

        // exec
        $this->curl->shouldReceive('exec')->once();

        // getinfo
        $this->curl->shouldReceive('getinfo')->once()->andReturnUsing(function ($arg) {
            return $arg == CURLINFO_HTTP_CODE ? 200 : 0;
        });

        // close
        $this->curl->shouldReceive('close')->once();

        $error = false;
        $httpClient = new DeepSeaCurlHttpClient($this->curl);

        try {
            $httpClient->send($url, $data, $method);
        } catch (DeepSeaException $ex) {
            $this->assertEquals(CURLE_COULDNT_CONNECT, $ex->getCode());
            $error = true;
        }
        $this->assertTrue($error);
    }

    public function testBuildHeader() {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;
        $headerKey = 'X-Test-With';
        $headerValue = uniqid('HEADER_', true);

        // Normal Add
        $this->prepareRequestForHeader($url, $method, sprintf("%s: %s", $headerKey, $headerValue));
        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $httpClient->addRequestHeader($headerKey, $headerValue);
        $httpClient->send($url, $data, $method);

        // Append but does not exists
        $this->prepareRequestForHeader($url, $method, sprintf("%s: %s", $headerKey, $headerValue));
        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $httpClient->addRequestHeader($headerKey, $headerValue, false);
        $httpClient->send($url, $data, $method);
    }

    public function testAppendHeader() {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;
        $headerKey = 'X-Test-With';
        $headerValue = uniqid('HEADER_', true);
        $anotherValue = uniqid('HEADER_2_', true);

        $this->prepareRequestForHeader($url, $method, sprintf("%s: %s,%s", $headerKey, $headerValue, $anotherValue));
        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $httpClient->addRequestHeader($headerKey, $headerValue);
        $httpClient->addRequestHeader($headerKey, $anotherValue, false);
        $httpClient->send($url, $data, $method);
    }

    private function prepareRequestForHeader($url, $method, $headerToFind) {
        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // error
        $this->curl->shouldReceive('error')->never()->andReturn('');

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once()->andReturnUsing(function ($arg) use ($url, $method, $headerToFind) {
            $this->assertEquals($url, $arg[CURLOPT_URL]);
            $this->assertEquals($method, $arg[CURLOPT_CUSTOMREQUEST]);
            $this->assertContains($headerToFind, $arg[CURLOPT_HTTPHEADER]);
        });

        // exec
        $this->curl->shouldReceive('exec')->once();

        // getinfo
        $this->curl->shouldReceive('getinfo')->times(2)->andReturnUsing(function ($arg) {
            return $arg == CURLINFO_HTTP_CODE ? 200 : 0;
        });

        // close
        $this->curl->shouldReceive('close')->once();

    }

    /**
     * @dataProvider responseHeaderAndContentProvider
     */
    public function testResponseHeader($version, $content, $headerSize, $expected) {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;

        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn($version);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once();

        // exec
        $this->curl->shouldReceive('exec')->once()->andReturn($content);

        // getinfo
        $this->curl->shouldReceive('getinfo')->times(2)->andReturnUsing(function ($arg) use ($headerSize) {
            switch ($arg) {
                case CURLINFO_HTTP_CODE:
                    return HTTP::OK;
                case CURLINFO_HEADER_SIZE:
                    return $headerSize;
                default:
                    return 0;
            }
        });

        // close
        $this->curl->shouldReceive('close')->once();

        $httpClient = new DeepSeaCurlHttpClient($this->curl);
        $result = $httpClient->send($url, $data, $method);
        foreach ($expected['header'] as $key => $value) {
            $this->assertEquals($value, $result->getHeader()->{$key});
        }
        foreach ($expected['content'] as $key => $value) {
            $this->assertEquals($value, $result->getContent()->{$key});
        }

    }

    public function responseHeaderAndContentProvider() {
        return array(
            array( // normal curl
                0x071E00 + 1,
                "HTTP 1/1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 30\r\nX-Processed-With: HTTP Test Processor\r\n\r\n{\"Code\": 200, \"Message\": \"OK\"}",
                0, // From Content-Length
                array(
                    'header' => array(
                        'Code' => 200,
                        'Status' => 'HTTP 1/1 200 OK',
                        'Content-Type' => 'application/json',
                        'X-Processed-With' => 'HTTP Test Processor',
                    ),
                    'content' => array(
                        'Code' => 200,
                        'Message' => 'OK'
                    )
                )
            ),
            array( // bugged curl with content-length
                0x071E00 - 1,
                "HTTP 1/1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 30\r\nX-Processed-With: HTTP Test Processor\r\n\r\n{\"Code\": 200, \"Message\": \"OK\"}",
                0, // From Content-Length
                array(
                    'header' => array(
                        'Code' => 200,
                        'Status' => 'HTTP 1/1 200 OK',
                        'Content-Type' => 'application/json',
                        'X-Processed-With' => 'HTTP Test Processor',
                    ),
                    'content' => array(
                        'Code' => 200,
                        'Message' => 'OK'
                    )
                )
            ),
            array( // bugged curl with proxy
                0x071E00 - 1,
                "HTTP 1/1 200 OK\r\nContent-Type: application/json\r\nX-Processed-With: HTTP Test Processor\r\n\r\nHTTP/1.0 200 Connection established\r\n\r\n{\"Code\": 418, \"Message\": \"I'm A Teapot\"}",
                90,
                array(
                    'header' => array(
                        'Code' => 200,
                        'Status' => 'HTTP 1/1 200 OK',
                        'Content-Type' => 'application/json',
                        'X-Processed-With' => 'HTTP Test Processor',
                    ),
                    'content' => array(
                        'Code' => 418,
                        'Message' => 'I\'m A Teapot'
                    )
                )
            ),
            array( // normal curl with proxy
                0x071E00 + 1,
                "HTTP 1/1 200 OK\r\nContent-Type: application/json\r\nX-Processed-With: HTTP Test Processor\r\n\r\nHTTP/1.0 200 Connection established\r\n\r\n{\"Code\": 418, \"Message\": \"I'm A Teapot\"}",
                90,
                array(
                    'header' => array(
                        'Code' => 200,
                        'Status' => 'HTTP 1/1 200 OK',
                        'Content-Type' => 'application/json',
                        'X-Processed-With' => 'HTTP Test Processor',
                    ),
                    'content' => array(
                        'Code' => 418,
                        'Message' => 'I\'m A Teapot'
                    )
                )
            ),
        );
    }
}
 