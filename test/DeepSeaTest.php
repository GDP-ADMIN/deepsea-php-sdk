<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/20/14
 * Time: 9:02 AM
 * GDP Venture © 2014
 */

namespace Test;

use DateTime;
use DateTimeZone;
use DeepSea\DeepSea;
use DeepSea\Entities\AccessToken;
use DeepSea\Entities\DeepSeaSession;
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\HttpClients\DeepSeaHttpResponse;
use DeepSea\HttpClients\DeepSeaRequest;
use DeepSea\Test\TestCase;
use Exception;
use Mockery\MockInterface;
use Mockery;
use stdClass;

class DeepSeaTest extends TestCase {

    /* @var MockInterface */
    protected $httpClient;
    protected $clientId;
    protected $clientSecret;
    protected $scope = array('test');
    protected $redirectUri;

    protected function setUp() {
        parent::setUp();
        $this->httpClient = Mockery::mock('DeepSea\HttpClients\DeepSeaHttpClientInterface');
        new DeepSeaRequest($this->httpClient);

        $this->clientId = uniqid('CLIENT_ID_');
        $this->clientSecret = uniqid('CLIENT_ID_SECRET_');
        $this->redirectUri = sprintf('http://%s.com', uniqid('', true));
    }

    protected function tearDown() {
        Mockery::close();
        new DeepSeaRequest();
        parent::tearDown();
    }

    public function testGetAuthURL() {
        $url = sprintf('http://%s.com', uniqid('', true));
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri, $url);
        $authUrl = $deepsea->getAuthURL();
        $state = DeepSeaSession::loadState();
        $expected = $url . '/oauth/authorize?response_type=code&client_id=' . $this->clientId . '&redirect_uri=' . urlencode($this->redirectUri) . '&scope=test&state=' . $state;
        $this->assertEquals($expected, $authUrl);
    }

    /**
     * @dataProvider accessTokenProvider
     */
    public function testSetAccessToken($token) {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $deepsea->setAccessToken($token);
        $token = DeepSeaSession::availableAccessToken();
        $this->assertEquals('ACCESS_TOKEN', (string) $token);
        $this->assertEquals('REFRESH_TOKEN', $token->refreshToken());
    }

    /**
     * @dataProvider accessTokenProvider
     */
    public function testGetRefreshToken($token) {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $deepsea->setAccessToken($token);
        $this->assertEquals('REFRESH_TOKEN', $deepsea->getRefreshToken());
    }

    public function testFailedParsingAccessToken() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $error = false;

        try {
            $deepsea->setAccessToken(new stdClass());
        } catch (DeepSeaException $ex) {
            $this->assertEquals(1003, $ex->getCode());
            $this->assertEquals('Invalid Access Token Object', $ex->getMessage());
            $error = true;
        }

        $this->assertTrue($error);
    }

    public function testFailProcessCode() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);

        $state = uniqid('STATE_', true);
        $code = uniqid('CODE_', true);

        DeepSeaSession::setState($state);
        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $result = new DeepSeaHttpResponse('', '{"code": 500, "error":"Access Denied"}');
            return $result;
        });

        $result = $deepsea->processAuthCode(array('code' => $code, 'state' => $state));
        $token = DeepSeaSession::availableAccessToken();
        $this->assertEquals($result->code, 500);
        $this->assertEquals($result->error, "Access Denied");
        $this->assertEmpty((string) $token);
    }

    public function testIncorrectStateProcessCode() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);

        $state = uniqid('STATE_', true);
        $code = uniqid('CODE_', true);
        DeepSeaSession::setState(uniqid('INVALID_', true));

        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $result = new DeepSeaHttpResponse('', '{"code": 500, "error":"Access Denied"}');
            return $result;
        });

        $error = false;

        try {
            $deepsea->processAuthCode(array('code' => $code, 'state' => $state));
        } catch (Exception $ex) {
            $this->assertEquals('1008', $ex->getCode());
            $this->assertEquals("Invalid State Returned By Server", $ex->getMessage());
            $error = true;
        }
        $this->assertTrue($error);
    }

    public function testProcessCode() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);

        $state = uniqid('STATE_', true);
        $code = uniqid('CODE_', true);

        DeepSeaSession::setState($state);
        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $date = new DateTime();
            $result = new DeepSeaHttpResponse('', sprintf('{"access_token": "ACCESS_TOKEN", "refresh_token": "REFRESH_TOKEN", "expires": %s}', $date->getTimestamp() + 3600));
            return $result;
        });

        $result = $deepsea->processAuthCode(array('code' => $code, 'state' => $state));
        $token = DeepSeaSession::availableAccessToken();
        $this->assertEquals('ACCESS_TOKEN', $result->access_token);
        $this->assertEquals('REFRESH_TOKEN', $result->refresh_token);
        $this->assertEquals($result->access_token, (string) $token);
        $this->assertEquals($result->refresh_token, $token->refreshToken());
    }

    public function testRefreshToken() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $date = new DateTime();
            $result = new DeepSeaHttpResponse('', sprintf('{"access_token": "NEW_ACCESS_TOKEN", "expires": %s}', $date->getTimestamp() + 3600));
            return $result;
        });
        $result = $deepsea->refreshAccessToken();
        $this->assertEquals('NEW_ACCESS_TOKEN', $result->access_token);
    }

    public function testFailedRefreshToken() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $result = new DeepSeaHttpResponse('', '{"message": "REJECTED", "code": 500}');
            return $result;
        });
        $result = $deepsea->refreshAccessToken();
        $this->assertEquals(500, $result->code);
        $this->assertEquals("REJECTED", $result->message);
    }

    public function testSendRequest() {
        $date = new DateTime();
        $expires = $date->getTimestamp() + 3600;
        $this->httpClient->shouldReceive('addRequestHeader')->once()->andReturnUsing(function ($key, $value) {
            $this->assertEquals('Authorization', $key);
            $this->assertEquals('Bearer ACCESS_TOKEN', $value);
        });
        $this->httpClient->shouldReceive('send')->andReturnUsing(function () {
            $result = new DeepSeaHttpResponse('', '{"message": "REJECTED", "code": 500}');
            return $result;
        });

        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $deepsea->setAccessToken(new AccessToken('ACCESS_TOKEN', 'REFRESH_TOKEN', $expires));
        $deepsea->sendRequest('/test');
    }

    public function testSendRequestTokenExpired() {
        $date = new DateTime();
        $expires = $date->getTimestamp() - 100; // Expired

        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        $deepsea->setAccessToken(new AccessToken('ACCESS_TOKEN', 'REFRESH_TOKEN', $expires));

        $error = false;
        try {
            $deepsea->sendRequest('/test');
        } catch (DeepSeaException $ex) {
            $this->assertEquals(1007, $ex->getCode());
            $this->assertEquals('Access Token Has Expired', $ex->getMessage());
            $error = true;
        }

        $this->assertTrue($error);
    }

    public function testSendRequestTokenEmpty() {
        @$deepsea = new DeepSea($this->clientId, $this->clientSecret, $this->scope, $this->redirectUri);
        new DeepSeaSession();

        $error = false;
        try {
            $deepsea->sendRequest('/test');
        } catch (DeepSeaException $ex) {
            $this->assertEquals(1007, $ex->getCode());
            $this->assertEquals('Access Token Is Required To Send A Request', $ex->getMessage());
            $error = true;
        }

        $this->assertTrue($error);
    }


    // Data Providers

    public function accessTokenProvider() {
        $accessToken = 'ACCESS_TOKEN';
        $refreshToken = 'REFRESH_TOKEN';
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $expires = $dateTime->getTimestamp() + 3600; // Now + 1 Hour
        return array(
            array(new AccessToken($accessToken, $refreshToken, $expires)),
            array(sprintf('{"access_token": "%s", "refresh_token": "%s", "expires": %s}', $accessToken, $refreshToken, $expires)),
            array(json_decode(sprintf('{"access_token": "%s", "refresh_token": "%s", "expires": %s}', $accessToken, $refreshToken, $expires))),
        );
    }


}
 