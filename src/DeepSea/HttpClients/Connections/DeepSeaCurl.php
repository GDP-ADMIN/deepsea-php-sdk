<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:45 AM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients\Connections;

/**
 * Curl Version Compatibility Const
 */
if (!defined('CURLE_SSL_CACERT')) { define('CURLE_SSL_CACERT', 60); }
if (!defined('CURLE_SSL_CACERT_BADFILE')) { define('CURLE_SSL_CACERT_BADFILE', 77); }

class DeepSeaCurl {

    /**
     * Curl Version which is unaffected by the proxy header length error.
     * http://curl.haxx.se/mail/tracker-2014-04/0017.html
     */
    const CURL_PROXY_QUIRK_VER = 0x071E00;

    /**
     * "Connection Established" header text
     */
    const CONNECTION_ESTABLISHED = "HTTP/1.0 200 Connection established\r\n\r\n";

    /**
     * @var resource Curl instance
     */
    protected $curl;

    public function setOpt($option, $value) {
        curl_setopt($this->curl, $option, $value);
    }

    public function setOptArray($options = array()) {
        curl_setopt_array($this->curl, $options);
    }

    public function getinfo($type) {
        return curl_getinfo($this->curl, $type);
    }

    public function getVersion() {
        return curl_version();
    }

    public function __construct() {
        $this->curl = curl_init();
        $this->setOptArray(array(
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
        ));
    }

    public function exec() {
        return curl_exec($this->curl);
    }

    public function close() {
        curl_close($this->curl);
    }

    public function errno() {
        return curl_errno($this->curl);
    }

    public function error() {
        return curl_error($this->curl);
    }

}