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
use Mockery\MockInterface;
use Mockery;

class DeepSeaCurlHttpClientTest extends TestCase {

    /* @var MockInterface $curl */
    protected $curl;

    public function setUp() {
        parent::setUp();
        $this->curl = Mockery::mock('DeepSea\HttpClients\Connections\DeepSeaCurl');
    }

    public function tearDown() {
        Mockery::close();
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

        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // error
        $this->curl->shouldReceive('error')->never()->andReturn('');

        // setOptArray
        $this->curl->shouldReceive('setOptArray')->once()->andReturnUsing(function ($arg) use ($url, $method, $headerKey, $headerValue) {
            $this->assertEquals($url, $arg[CURLOPT_URL]);
            $this->assertEquals($method, $arg[CURLOPT_CUSTOMREQUEST]);
            $headerToFind = sprintf("%s: %s", $headerKey, $headerValue);
            $found = false;
            foreach ($arg[CURLOPT_HTTPHEADER] as $header) {
                if ($header === $headerToFind) { $found = true; }
            }
            $this->assertTrue($found);
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
        $httpClient->addRequestHeader($headerKey, $headerValue);
        $httpClient->send($url, $data, $method);
    }

    public function testAppendHeader() {
        $url = sprintf('http://%s.com', uniqid('', true));
        $data = array();
        $method = HTTP::GET;
        $headerKey = 'X-Test-With';
        $headerValue = uniqid('HEADER_', true);
        $anotherValue = uniqid('HEADER_2_', true);

        // getVersion
        $this->curl->shouldReceive('getVersion')->twice()->andReturn(0x071E00 + 1);

        // open
        $this->curl->shouldReceive('open')->once();

        // errno
        $this->curl->shouldReceive('errno')->once()->andReturn(CURLE_OK);

        // error
        $this->curl->shouldReceive('error')->never()->andReturn('');

        // setOptArray
        $headerToFind = $headerToFind = sprintf("%s: %s,%s", $headerKey, $headerValue, $anotherValue);
        $this->curl->shouldReceive('setOptArray')->once()->andReturnUsing(function ($arg) use ($url, $method, $headerToFind) {
            $this->assertEquals($url, $arg[CURLOPT_URL]);
            $this->assertEquals($method, $arg[CURLOPT_CUSTOMREQUEST]);
            $found = false;
            foreach ($arg[CURLOPT_HTTPHEADER] as $header) {
                if ($header === $headerToFind) { $found = true; }
            }
            $this->assertTrue($found);
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
        $httpClient->addRequestHeader($headerKey, $headerValue);
        $httpClient->addRequestHeader($headerKey, $anotherValue, false);
        $httpClient->send($url, $data, $method);
    }
}
 