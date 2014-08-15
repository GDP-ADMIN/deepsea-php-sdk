<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/14/14
 * Time: 12:52 PM
 * GDP Venture © 2014
 */

namespace Test\Entities;

use DeepSea\Entities\Session;
use DeepSea\Test\TestCase;

class SessionTest extends TestCase {

    public function testSetGet() {
        @$session = Session::getInstance();
        $sessionId = uniqid('SESS_');
        $sessionValue = uniqid('VALUE_');

        $session->{$sessionId} = $sessionValue;
        $this->assertEquals($sessionValue, $session->{$sessionId});
    }

    public function testIssetUnset() {
        @$session = Session::getInstance();
        $sessionId = uniqid('SESS_');
        $sessionValue = uniqid('VALUE_');

        $this->assertFalse(isset($session->{$sessionId}));

        $session->{$sessionId} = $sessionValue;
        $this->assertTrue(isset($session->{$sessionId}));

        unset($session->{$sessionId});
        $this->assertFalse(isset($session->{$sessionId}));
    }

    public function testDestroy() {
        /* @var Session $session */
        @$session = Session::getInstance();
        $sessionId = uniqid('SESS_');
        $sessionValue = uniqid('VALUE_');

        $session->{$sessionId} = $sessionValue;
        $this->assertEquals($sessionValue, $session->{$sessionId});

        $this->assertTrue($session->destroy());
        $this->assertFalse($session->destroy());
        $this->assertNull($session->{$sessionId});
    }


}
 