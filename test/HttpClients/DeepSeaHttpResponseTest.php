<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/19/14
 * Time: 1:49 PM
 * GDP Venture © 2014
 */

namespace Test\HttpClients;

use DeepSea\HttpClients\DeepSeaHttpResponse;
use DeepSea\Test\TestCase;

class DeepSeaHttpResponseTest extends TestCase {
    protected function setUp() {
        parent::setUp();
    }

    protected function tearDown() {
        parent::tearDown();
    }

    /**
     * @dataProvider constructDataProvider
     */
    public function testSetAndGet($head, $content, $expectedHead, $expectedContent) {
        // String construct
        $response = new DeepSeaHttpResponse($head, $content);
        foreach ($expectedHead as $key => $value) {
            $header = $response->getHeader();
            $this->assertEquals($value, $header->{$key});
        }
        foreach ($expectedContent as $key => $value) {
            $content = $response->getContent();
            $this->assertEquals($value, $content->{$key});
        }
    }

    public function constructDataProvider() {
        return array(
            // string header
            array('{"Code": 200, "Status": "OK", "X-Requested-With": "Test"}', '{"key": "value"}', array('Code'=>200, 'Status'=> 'OK', 'X-Requested-With'=> 'Test'), array('key' => 'value')),
            // array header
            array(array('Code'=>200, 'Status'=> 'OK', 'X-Requested-With'=> 'Test'), '{"key": "value"}', array('Code'=>200, 'Status'=> 'OK', 'X-Requested-With'=> 'Test'), array('key' => 'value')),
            // object header
            array(json_decode('{"Code": 200, "Status": "OK", "X-Requested-With": "Test"}'), '{"key": "value"}', array('Code'=>200, 'Status'=> 'OK', 'X-Requested-With'=> 'Test'), array('key' => 'value')),
        );
    }

}
 