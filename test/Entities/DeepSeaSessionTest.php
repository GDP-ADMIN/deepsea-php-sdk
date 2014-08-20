<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/15/14
 * Time: 3:29 PM
 * GDP Venture © 2014
 */

namespace Test\Entities;

use DateTime;
use DeepSea\Entities\AccessToken;
use DeepSea\Entities\DeepSeaSession;
use DeepSea\Test\TestCase;

class DeepSeaSessionTest extends TestCase {

    public function setUp() {
        parent::setUp();
        date_default_timezone_set("UTC");
    }

    public function testSessionToken() {
        @$available = DeepSeaSession::availableAccessToken();
        $this->assertNull($available);

        $session = new DeepSeaSession();
        @$available = DeepSeaSession::availableAccessToken();
        $this->assertNull($available);
        $this->assertNull($session->getAccessToken());

        $fakeToken = uniqid('TOKEN_');
        $validity = new DateTime();
        $validity = $validity->getTimestamp() + 3600;
        $token  = new AccessToken($fakeToken, null, $validity);
        $session = new DeepSeaSession($token);

        @$available = DeepSeaSession::availableAccessToken();
        $this->assertNotNull($available);
        $this->assertEquals($token, $available);
        $this->assertEquals($token, $session->getAccessToken());

        $session->setAccessToken(null);
        $this->assertNull($session->getAccessToken());
    }

    public function testSaveLoadState() {
        $state = uniqid('STATE_');
        @DeepSeaSession::setState($state);
        $this->assertEquals($state, DeepSeaSession::loadState());
    }

}
 