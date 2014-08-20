<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/19/14
 * Time: 1:27 PM
 * GDP Venture © 2014
 */

namespace Test\HttpClients;

use DeepSea\Entities\HTTP;
use DeepSea\HttpClients\DeepSeaRequest;
use DeepSea\Test\TestCase;
use Mockery\MockInterface;
use Mockery;

class DeepSeaRequestTest extends TestCase {

    /* @var MockInterface */
    protected $httpClient;

    public function setUp() {
        parent::setUp();
        $this->httpClient = Mockery::mock('DeepSea\HttpClients\DeepSeaHttpClientInterface');
    }

    public function tearDown() {
        // clear static context
        new DeepSeaRequest(null);
        Mockery::close();
        parent::tearDown();
    }


    public function testSetRequestHeaders() {
        $request = new DeepSeaRequest($this->httpClient);
        $headers = array(
            'X-Test-With' => uniqid('VALUE_'),
            'X-Run-With' => uniqid('VALUE_'),
            'X-Open-With' => uniqid('VALUE_'),
        );
        $request->setRequestHeaders($headers);

        $this->httpClient->shouldReceive('send')->once();
        $this->httpClient->shouldReceive('addRequestHeader')->times(count($headers))->andReturnUsing(function ($key, $value) use ($headers) {
            $this->assertEquals($headers[$key], $value);
        });
        $request->send('', array(), HTTP::GET);
    }

    public function testHttpClients() {
        new DeepSeaRequest($this->httpClient);
        $this->assertEquals($this->httpClient, DeepSeaRequest::getHttpClient());
    }

    /**
     * @dataProvider urlAndMethodProvider
     */
    public function testSend($url, $method, $data) {
        $request = new DeepSeaRequest($this->httpClient);

        // No Header Set
        $this->httpClient->shouldReceive('addRequestHeader')->never();
        $this->httpClient->shouldReceive('send')->once()->andReturnUsing(function ($arg_url, $arg_params, $arg_method) use ($url, $data, $method) {
            $this->assertEquals($url, $arg_url);
            $this->assertEquals($data, $arg_params);
            $this->assertEquals($method, $arg_method);
        });

        $request->send($url, $data, $method);
    }

    public function urlAndMethodProvider() {
        return array(
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::GET, array()),
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::DELETE, array()),
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::POST, array('data' => uniqid('DATA_POST_'))),
            array(sprintf('http://%s.com', uniqid('', true)), HTTP::PUT, array('data' => uniqid('DATA_PUT_'))),
        );
    }

    /**
     * @dataProvider urlAndQuerystringProvider
     */
    public function testQueryString($url, $data, $expected) {
        $request = new DeepSeaRequest($this->httpClient);

        // No Header Set
        $this->httpClient->shouldReceive('addRequestHeader')->never();
        $this->httpClient->shouldReceive('send')->once()->andReturnUsing(function ($url) use ($expected) {
            $this->assertEquals($expected, $url);
        });

        $request->send($url, $data, HTTP::GET);
    }

    public function urlAndQuerystringProvider() {
        return array(
            array('http://foo.com', array('test' => true), 'http://foo.com?test=1'),
            array('http://foo.com', array('test' => 4242), 'http://foo.com?test=4242'),
            array('http://foo.com', array('param_1' => 'value_1', 'param_2' => 'value_2'), 'http://foo.com?param_1=value_1&param_2=value_2'),
            array('http://foo.com', array('c' => 'value_1', 'a' => 'value_2', 'b' => 'value_3'), 'http://foo.com?c=value_1&a=value_2&b=value_3'),
            array('http://foo.com?prev=false', array('param_1' => 'value_1', 'param_2' => 'value_2'), 'http://foo.com?param_1=value_1&param_2=value_2&prev=false'),
            array('http://foo.com?next=true&test=ok', array('c' => 'value_1', 'a' => 'value_2', 'b' => 'value_3'), 'http://foo.com?c=value_1&a=value_2&b=value_3&next=true&test=ok'),
        );
    }
}
 