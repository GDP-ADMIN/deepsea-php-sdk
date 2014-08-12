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
use DeepSea\Exceptions\DeepSeaException;
use DeepSea\HttpClients\DeepSeaHttpResponse as Response;
use DeepSea\HttpClients\DeepSeaRequest;

class DeepSea {
    const SDK_VERSION      = '2.0.1-dev';
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

    /**
     * DeepSea SDK Class
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param array $scope
     * @param string $redirectUri
     * @param null $host DeepSea API host (optional, default: https://api.deepsea.co.id/ )
     * @param null $version API version (optional, default: current)
     * @throws DeepSeaException
     */
    public function __construct($clientId, $clientSecret, $scope, $redirectUri, $host = null, $version = null) {

        // Due to SSL Problem, http file stream client is disable for now
        if (!function_exists('curl_init') || !is_callable('curl_init')) {
            throw DeepSeaException::create("DeepSea SDK Require cURL extension", 2001);
        }

        $host = ($host) ? : self::HOST;
        $version = ($version) ? : self::API_VERSION;
        if (!(strpos($version, '/') === 0)) { $version = '/' . $version; }

        $this->API_KEY          = $clientId;
        $this->API_SECRET       = $clientSecret;
        $this->API_HOST         = $host . $version;
        $this->scope            = implode(',', $scope);
        $this->REDIRECT_URI     = $redirectUri;
        $this->auth_url         = $host . '/oauth/authorize';
        $this->access_token_url = $host . '/oauth/accesstoken';

        $this->setAccessToken(DeepSeaSession::availableAccessToken() ? : new AccessToken());
        $this->httpClient = new DeepSeaRequest();

        date_default_timezone_set("UTC");
    }

    /**
     * Redirect User To DeepSea Login Screen
     */
    public function authorize() {
        if (!isset($_GET[Response::CODE])) { $this->redirect($this->getAuthURL()); }
    }

    /**
     * Set previously obtained Access Token
     * Accept 3 kind of input
     *
     * @param AccessToken|string|object $token Accept: AccessToken, Json serialized Access Token, or Object that contain access_token, refresh_token, and expires
     * @throws Exceptions\DeepSeaException
     */
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

    /**
     * Get Refresh Token obtained from server
     *
     * @return null|string
     */
    public function getRefreshToken() {
        return ($this->session->getAccessToken() !== null && $this->session->getAccessToken()->isAlive()) ? $this->session->getAccessToken()->refreshToken() : null;
    }

    /**
     * Exchange response code from server with actual Access Token
     *
     * @param array $get
     * @return object
     */
    public function processAuthCode($get = array()) {
        if (!isset($get[Response::CODE])) { $this->authorize(); }
        if (isset($get['state'])) { DeepSeaSession::setState($get['state']); }
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

    /**
     * Refresh/Extend currently obtained Access Token
     *
     * @param null|string $refresh_token If Refresh Token is not given, use current Refresh Token (automatically try to determine Refresh Token)
     * @return object new Access Token and expiration (timestamp)
     */
    public function refreshAccessToken($refresh_token = null) {
        $params = array(
            "client_id"     => $this->API_KEY,
            "client_secret" => $this->API_SECRET,
            "grant_type"    => GRANT::REFRESH,
            "refresh_token" => $refresh_token ? : $this->getRefreshToken()
        );
        $response = $this->httpClient->send($this->access_token_url, $params, HTTP::GET);
        if (isset($response->getContent()->access_token)) {
            $newToken = $response->getContent();
            $this->setAccessToken(
                new AccessToken($newToken->access_token, $this->getRefreshToken(), $newToken->expires)
            );
        }
        return $response->getContent();
    }

    /**
     * Send request to API Server
     *
     * @param string $path Relative path to endpoint (without version). Example: '/user'
     * @param array $data Data to be send to server
     * @param string $method Http Method (GET, POST, PUT, or DELETE)
     * @return Response
     * @throws Exceptions\DeepSeaException
     */
    public function sendRequest($path, $data = array(), $method = HTTP::GET) {
        $accessToken = $this->session->getAccessToken();
        if ($accessToken === null || empty($accessToken)) {
            throw DeepSeaException::create('Access Token Is Required To Send A Request', 1007);
        }
        $this->httpClient->setRequestHeaders(array(
            'Authorization' => sprintf('Bearer %s', $accessToken)
        ));
        return $this->request($this->API_HOST . $path, $data, $method);
    }


    /**
     * Get Authorization URL, for manual redirection to login screen
     *
     * @return string
     */
    public function getAuthURL() {
        $parameters = array(
            "response_type" => Response::CODE,
            "client_id"     => $this->API_KEY,
            "redirect_uri"  => $this->getRedirectURL(),
            "scope"         => $this->scope,
            "state"         => uniqid('DS')
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