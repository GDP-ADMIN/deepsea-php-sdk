<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/19/14
 * Time: 3:25 PM
 * GDP Venture © 2014
 */

namespace Test\HttpClients;

use DeepSea\Entities\HTTP;
use DeepSea\HttpClients\DeepSeaFileStreamHttpClient;
use DeepSea\Test\TestCase;
use Mockery\MockInterface;
use Mockery;


class DeepSeaFileStreamHttpClientTest extends TestCase {

    /* @var MockInterface $stream */
    protected $stream;

    protected function setUp() {
        parent::setUp();
        $this->stream = Mockery::mock('DeepSea\HttpClients\Connections\DeepSeaFileStream');
    }

    protected function tearDown() {
        Mockery::close();
        new DeepSeaFileStreamHttpClient();
        parent::tearDown();
    }

    /**
     * @dataProvider urlAndMethodProvider
     */
    public function testSendWithMethod($url, $data, $method, $call) {
        $this->stream->shouldReceive('setOpt')->times($call);
        $this->stream->shouldReceive('open')->once()->andReturnUsing(function ($arg) use ($url) {
            $this->assertEquals($url, $arg);
        });
        $this->stream->shouldReceive('exec')->once()->andReturn(
            array(
                'header' => array('HTTP/1.1 200 OK', 'Content-Type: application: json', 'X-Bla-With: Mrrgl-mrgl-mrgl'),
                'content'=>'{"Code": 418, "Message": "I\'m A Teapot"}'
            )
        );
        $this->stream->shouldReceive('close')->once();

        $httpClient = new DeepSeaFileStreamHttpClient($this->stream);
        $httpClient->send($url, $data, $method);
    }

    public function urlAndMethodProvider() {
        return array(
            array(
                sprintf('http://%s.com', uniqid('', true)),
                array(),
                HTTP::GET,
                1
            ),
            array(
                sprintf('http://%s.com', uniqid('', true)),
                array(),
                HTTP::POST,
                2
            ),
            array(
                sprintf('http://%s.com', uniqid('', true)),
                array(),
                HTTP::PUT,
                2
            ),
            array(
                sprintf('http://%s.com', uniqid('', true)),
                array(),
                HTTP::DELETE,
                2
            ),
        );
    }

}
 