<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/15/14
 * Time: 3:04 PM
 * GDP Venture © 2014
 */

namespace Test\Entities;

use DateTime;
use DeepSea\Entities\AccessToken;
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\Test\TestCase;
use Exception;

class AccessTokenTest extends TestCase {

    public function setUp() {
        parent::setUp();
        date_default_timezone_set("UTC");
    }

    public function testCreateToken() {
        $fakeToken = uniqid('TOKEN_');
        $validity = new DateTime();

        $token = new AccessToken();
        $this->assertEmpty((string) $token);
        $this->assertFalse($token->isAlive());
        $this->assertNull($token->refreshToken());

        $token = new AccessToken($fakeToken, null, $validity->getTimestamp() + 3600);
        $this->assertEquals($fakeToken, (string) $token);
        $this->assertTrue($token->isAlive());
        $this->assertNull($token->refreshToken());

        $refreshToken = uniqid('REFRESH_');
        $token = new AccessToken($fakeToken, $refreshToken, $validity->getTimestamp() - 3600);
        $this->assertEmpty((string) $token);
        $this->assertFalse($token->isAlive());
        $this->assertEquals($refreshToken, $token->refreshToken());
    }

    public function testSerializeUnserialize() {
        $fakeToken = uniqid('TOKEN_');
        $refreshToken = uniqid('REFRESH_');
        $validity = new DateTime();
        $validity = $validity->getTimestamp() + 3600;
        $unserialized = new AccessToken();

        $token = new AccessToken();
        $serialized = $token->serialize();
        $unserialized->unserialize($serialized);
        $this->assertEquals($token, $unserialized);

        $token = new AccessToken($fakeToken, $refreshToken, $validity);
        $serialized = $token->serialize();
        $unserialized->unserialize($serialized);
        $this->assertEquals($token, $unserialized);

        $this->assertEquals($fakeToken, (string) $unserialized);
        $this->assertEquals($refreshToken, $unserialized->refreshToken());
        $this->assertTrue($unserialized->isAlive());
    }

    // Due to phpunit namespace, we cant use @expectedException
    public function testFailedUnserialize() {
        $invalidJson = sprintf('{access_token: "%s", ', uniqid("TOKEN_"));
        $token = new AccessToken();
        $error = false;

        try {
            $token->unserialize($invalidJson);
        } catch (Exception $ex) {
            $this->assertTrue($ex instanceof DeepSeaException);
            $this->assertEquals($ex->getMessage(), 'Failed to Unserialize Access Token');
            $this->assertEquals($ex->getCode(), 1004);
            $error = true;
        }

        // make sure exception is actually thrown
        $this->assertTrue($error);

    }

}
 