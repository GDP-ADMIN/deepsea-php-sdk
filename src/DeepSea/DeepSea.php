<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 8:35 AM
 * GDP Venture © 2014
 */

namespace DeepSea;

use DeepSea\Entities\AccessToken;
use DeepSea\Entities\DeepSeaSession;
use DeepSea\Entities\GRANT;
use DeepSea\Entities\HTTP;
use DeepSea\Entities\Session;
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\HttpClients\DeepSeaHttpResponse as Response;
use DeepSea\HttpClients\DeepSeaRequest;

class DeepSea {
    const SDK_VERSION      = '2.0.1 dev';
    const API_VERSION      = 'v2';
    const HOST             = 'https://api.deepsea.co.id';

    const CONTENT_ENCODING = 'utf-8';
    const DATE_FORMAT      = 'Y-m-d\TH:i:s\Z';

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var DeepSeaSession
     */
    protected $session;

    /**
     * @var DeepSeaRequest
     */
    protected $httpClient;

    private $API_KEY;
    private $API_SECRET;
    private $API_HOST;
    private $REDIRECT_URI;

    private $auth_url;
    private $access_token_url;

    public function __construct($clientId, $clientSecret, $scope, $redirectUri, $host = null, $version = null) {
        $host = ($host) ? : DeepSea::HOST;
        $version = ($version) ? : DeepSea::API_VERSION;
        if (!(strpos($version, '/') === 0)) { $version = '/' . $version; }

        $this->API_KEY          = $clientId;
        $this->API_SECRET       = $clientSecret;
        $this->API_HOST         = $host . $version;
        $this->scope            = implode(',', $scope);
        $this->REDIRECT_URI     = $redirectUri;
        $this->auth_url         = $host . '/oauth/authorize';
        $this->access_token_url = $host . '/oauth/accesstoken';

        $session = Session::getInstance();
        if (isset($session->{DeepSeaSession::TOKEN_STORAGE})) {
            $this->setAccessToken($session->{DeepSeaSession::TOKEN_STORAGE});
        }
        $this->httpClient = new DeepSeaRequest();

        date_default_timezone_set("UTC");
    }

    public function authorize() {
        if (!isset($_GET[Response::CODE])) { $this->redirect($this->getAuthURL()); }
    }

    public function setAccessToken($token = null) {
        $accessToken = null;
        if (is_string($token)) {
            $accessToken = new AccessToken();
            $accessToken->unserialize($token);
        } else if (!($token instanceof AccessToken) && (isset($token->access_token) && isset($token->refresh_token) && isset($token->expires))) {
            $accessToken = new AccessToken($token->access_token, $token->refresh_token, $this->expires);
        } else if ($token instanceof AccessToken) {
            $accessToken = $token;
        } else {
            throw DeepSeaException::create('Invalid Access Token Object', 1003);
        }

        $this->session = new DeepSeaSession($accessToken);
    }

    public function getRefreshToken() {
        return ($this->session->getAccessToken() !== null && $this->session->getAccessToken()->isAlive()) ? $this->session->getAccessToken()->refreshToken() : null;
    }

    public function processAuthCode($get = array()) {
        if (!isset($get[Response::CODE])) { $this->authorize(); }
        if (isset($get['state'])) {
            $session = Session::getInstance();
            $session->OAUTH_STATE = $get['state'];
        }
        $params = array(
            "client_id"     => $this->API_KEY,
            "client_secret" => $this->API_SECRET,
            "grant_type"    => GRANT::AUTH_CODE,
            "redirect_uri"  => $this->getRedirectURL(),
            Response::CODE  => $get[Response::CODE]
        );
        // TODO: update HTTP::GET to HTTP::POST if process code already support POST
        $response = $this->httpClient->send($this->access_token_url, $params, HTTP::GET);
        if (isset($response->getContent()->access_token)) {
            $this->setAccessToken(json_encode($response->getContent()));
        }
        return $response->getContent();
    }

    public function refreshAccessToken($refresh_token = null) {
        $params = array(
            "client_id"     => $this->API_KEY,
            "client_secret" => $this->API_SECRET,
            "grant_type"    => GRANT::REFRESH,
            "refresh_token" => ($refresh_token) ? : $this->getRefreshToken()
        );
        $response = $this->httpClient->send($this->access_token_url, $params, HTTP::GET);
        if (isset($response->getContent()->access_token)) {
            $newToken = $response->getContent();
            $newToken->refresh_token = $this->getRefreshToken();
            $this->setAccessToken(json_encode($newToken));
        }
        return $response->getContent();
    }

    public function sendRequest($path, $data = array(), $method = HTTP::GET) {
        if ($this->session->getAccessToken() === null) {
            throw DeepSeaException::create('Access Token Is Required To Send A Request', 1007);
        }
        $this->httpClient->setRequestHeaders(array(
            'Authorization' => sprintf('Bearer %s', $this->session->getAccessToken())
        ));
        return $this->request($this->API_HOST . $path, $data, $method);
    }

    public function getAuthURL() {
        $parameters = array(
            "response_type" => Response::CODE,
            "client_id"     => $this->API_KEY,
            "redirect_uri"  => $this->getRedirectURL(),
            "scope"         => $this->scope,
            "state"         => "DS" . uniqid()
        );
        return $this->auth_url . '?' . http_build_query($parameters, null, '&');
    }

    final private function redirect($url) {
        header("Location: " . $url);
    }

    private function request($url, $data = array(), $method = HTTP::GET) {
        return $this->httpClient->send($url, $data, $method);
    }

    private function getRedirectURL() {
        return isset($this->REDIRECT_URI) ? $this->REDIRECT_URI : (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'));
    }

}