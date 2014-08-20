<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/15/14
 * Time: 4:27 PM
 * GDP Venture © 2014
 */

namespace Test\Exceptions;

use DeepSea\Exceptions\DeepSeaException;
use DeepSea\Test\TestCase;

class DeepSeaExceptionTest extends TestCase {

    public function testConstruct() {
        $message = uniqid("MESSAGE_");
        $code = rand(100, 999);
        $exc = new DeepSeaException($message, $code);

        $this->assertEquals($message, $exc->getMessage());
        $this->assertEquals($code, $exc->getCode());
    }

    public function testCreate() {
        $message = uniqid("MESSAGE_");
        $code = rand(100, 999);
        $exc = DeepSeaException::create($message, $code);

        $this->assertEquals($message, $exc->getMessage());
        $this->assertEquals($code, $exc->getCode());
    }

}
 