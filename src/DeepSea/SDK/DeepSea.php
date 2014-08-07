<?php
/**
 * Created by JetBrains PhpStorm.
 * User: glenn.kristanto
 * Date: 10/9/13
 * Time: 9:48 AM
 * GDP Venture © 2013
 */

namespace DeepSea\SDK;

class DSCONFIG {
    public static $CLIENT_ID     = "1";
    public static $CLIENT_SECRET = "001";
    public static $SCOPE         = array(SCOPE::ALL);

    public static $API_HOST      = "http://api.deepsea.co.id";
    public static $VERSION       = "/v2";

    // Optional, if not defined will use current URL without query string
    // Server will still check redirect URL, it has to match with registered
    public static $REDIRECT_URI  = "http://localhost/auth";
}

class DeepSea {
    static $CONTENT_ENCODING = 'utf-8';
    static $DATE_FORMAT      = 'Y-m-d\TH:i:s\Z';

    protected $method;
    protected $path;
    protected $query;
    protected $SCOPE;
    protected $access_token  = null;
    protected $refresh_token = null;

    private $API_KEY;
    private $API_SECRET;
    private $API_HOST;
    private $REDIRECT_URI;

    private $auth_url;
    private $access_token_url;
    private $use_curl;

    public function __construct($clientId, $clientSecret, $scope, $redirectUri, $host = null, $version = null) {
        $this->use_curl = is_callable('curl_init');

        $host = ($host) ?: DSCONFIG::$API_HOST;
        $version = ($version) ?: DSCONFIG::$VERSION;

        $this->API_KEY          = $clientId;
        $this->API_SECRET       = $clientSecret;
        $this->API_HOST         = $host . $version;
        $this->SCOPE            = implode(",", $scope);
        $this->REDIRECT_URI     = $redirectUri;
        $this->auth_url         = $host . '/oauth/authorize';
        $this->access_token_url = $host . '/oauth/accesstoken';

        date_default_timezone_set("UTC");
    }

    public function authorize() {
        if (!isset($_GET['code'])) { DeepSea::redirect($this->getAuthURL()); }
    }

    public function setAccessToken($token = null) {
        $this->access_token = $token;
    }

    public function getRefreshToken() {
        return ($this->access_token !== null && isset($this->access_token->refresh_token)) ? $this->access_token->refresh_token : null;
    }

    public function processAuthCode($get = array()) {
        if (!isset($get['code'])) { $this->authorize(); }
        if (isset($get['state'])) {
            if (function_exists('session_start')) {
                if (!$this->is_session_started()) {session_start();}
                if (isset($get['state'])) { $_SESSION['OAUTH_STATE'] = $get['state']; }
            }
        }
        $param = array(
            "grant_type"    => GRANT::AUTH_CODE,
            "redirect_uri"  => $this->getRedirectURL(),
            "code"          => $get['code']
        );
        $response = $this->OAuthRequest($param);
        if (isset($response->content->access_token)) {
            $this->access_token = $response->content;
        }
        return $response->content;
    }

    public function refreshAccessToken($refresh_token) {
        $param = array(
            "grant_type" => GRANT::REFRESH,
            "refresh_token" => $refresh_token
        );
        $response = $this->OAuthRequest($param);
        if (isset($response->content->access_token)) {
            $this->access_token->access_token = $response->content->access_token;
            $this->access_token->expires      = $response->content->expires;
            $this->access_token->expires_in   = $response->content->expires_in;
        }
        return $response->content;
    }

    public function sendRequest($path, $data = array(), $method = HTTP::GET, $return_type = TYPE::OBJECT) {
        $url = $this->API_HOST . $path;
        if ($this->access_token !== null && isset($this->access_token->access_token)) { $data['access_token'] = $this->access_token->access_token; }
        return $this->request($url, $data, $method, $return_type);
    }

    protected function OAuthRequest($additional_param = array()) {
        $basic_param = array(
            "client_id"     => $this->API_KEY,
            "client_secret" => $this->API_SECRET,
        );
        $param = array_merge($basic_param, $additional_param);
        return $this->request($this->access_token_url, $param, HTTP::GET, TYPE::OBJECT);
    }

    final private function parseHeader($header) {
        $http_response_header = explode("\r\n", $header);
        $result = array();
        $result["Status"] = $http_response_header[0];

        $matches = array();
        preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
        $result["Code"] = $matches[1];

        for ($i = 1; $i < sizeof($http_response_header); $i++) {
            if (strlen($http_response_header[$i]) > 0) {
                $index = stripos($http_response_header[$i], ":");
                $result[trim(substr($http_response_header[$i], 0, $index))] = trim(substr($http_response_header[$i], $index + 1));
            }
        }
        return json_encode($result);
    }

    final private function buildHeader($return_type = TYPE::JSON) {
        if ($this->use_curl) { $curl_ver = curl_version(); $fetcher = "Curl/" . $curl_ver['features']; } else { $fetcher = "FOpen"; }
        $header = array(
            "Accept: " . ($return_type == TYPE::OBJECT ? TYPE::JSON : $return_type),
            "Connection: keep-alive",
            "Content-type: application/x-www-form-urlencoded; charset=" . self::$CONTENT_ENCODING,
            "User-Agent: " . sprintf("DeepSea/1.0 (%s; %s; %s) PHP/%s %s (LIB, DeepSea API Client)", php_uname('s'), php_uname('r'), php_uname('m'), phpversion(), $fetcher)
        );
        if ($this->access_token !== null) {
            if (isset($this->access_token->access_token) && isset($this->access_token->token_type) && strtolower($this->access_token->token_type) === 'bearer') {
                $header = array_merge($header, array(sprintf("Authorization: %s %s", ucfirst($this->access_token->token_type), $this->access_token->access_token)));
            }
        }
        sort($header);
        return $header;
    }

    final private function sendCurlRequest($fullUrl, $return_type) {
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeader($return_type));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->method !== HTTP::GET) { curl_setopt ($ch, CURLOPT_POSTFIELDS, $this->query); }

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header      = $this->parseHeader(substr($response, 0, $header_size));
        $body        = substr($response, $header_size);

        curl_close($ch);
        return ($return_type === TYPE::OBJECT) ? new Result($header, $body) : $body;
    }

    final private function sendNoCurlRequest($fullUrl, $return_type) {
        $options = array(
            'http' => array(
                'header'  => implode("\r\n", $this->buildHeader( $return_type)),
                'method'  => $this->method,
                'content' => ($this->method === HTTP::GET ? "" : $this->query)
            )
        );
        $context  = stream_context_create($options);
        $fp = fopen($fullUrl, 'rb', false, $context);
        if ($fp) {
            $meta_data = stream_get_meta_data($fp);
            $header = $this->parseHeader(implode("\r\n", $meta_data['wrapper_data']));
            $body   = stream_get_contents($fp);
        }
        return ($return_type === TYPE::OBJECT) ? new Result($header, $body) : $body;
    }

    final private function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    private function request($url, $data = array(), $method = HTTP::GET, $return_type = TYPE::TEXT) {
        $this->query = http_build_query($data);
        $this->method = $method;
        $url = $url . ($this->method === HTTP::GET ? "?" . $this->query : "");
        return ($this->use_curl ? $this->sendCurlRequest($url, $return_type) : $this->sendNoCurlRequest($url, $return_type));
    }

    public function getAuthURL() {
        $parameters = array(
            "response_type" => APIRESPONSE::CODE,
            "client_id"     => $this->API_KEY,
            "redirect_uri"  => $this->getRedirectURL(),
            "scope"         => $this->SCOPE,
            "state"         => "DS" . uniqid()
        );
        return $this->auth_url . '?' . http_build_query($parameters);
    }

    private function getRedirectURL() {
        return isset($this->REDIRECT_URI) ? $this->REDIRECT_URI : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "https://" : "http://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?'));
    }


    function is_session_started() {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? true : false;
        } else {
            return session_id() === '' ? false : true;
        }
    }
}

class Result {
    public $header;
    public $content;

    public function __construct($header, $content) {
        $this->header  = json_decode($header);
        $this->content = json_decode($content);
    }

}

class HTTP {
    const GET    = 'GET';
    const POST   = 'POST';
    const DELETE = 'DELETE';
    const PUT    = 'PUT';

    const OK              = 200;
    const CREATED         = 201;
    const ACCEPTED        = 202;
    const NO_CONTENT      = 204;
    const BAD_REQUEST     = 400;
    const UNAUTHORIZED    = 401;
    const FORBIDDEN       = 403;
    const NOT_FOUND       = 403;
    const NOT_ALLOWED     = 405;
    const GONE            = 420;
    const ERROR           = 500;
    const NOT_IMPLEMENTED = 501;
    const UNAVAILABLE     = 503;
}

class TYPE {
    const TEXT       = 'text/plain';
    const JSON       = 'application/json';
    const JSONP      = 'application/javascript';
    const XML        = 'application/xml';
    const OBJECT     = 'application/object';
}

class GRANT {
    const AUTH_CODE   = 'authorization_code';
    const PASSWORD    = 'password';
    const CREDENTIALS = 'client_credentials';
    const REFRESH     = 'refresh_token';
}

class APIRESPONSE {
    const CODE  = 'code';
    const TOKEN = 'token';
}

class SCOPE {
    const ALL     = 'all';
    const LIMITED = 'limited';
    const BASIC   = 'basic';
}

?>