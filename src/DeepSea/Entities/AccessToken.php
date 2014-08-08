<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 8:48 AM
 * GDP Venture © 2014
 */

namespace DeepSea\Entities;

use DateTime;
use DeepSea\Exceptions\DeepSeaException;
use Serializable;

class AccessToken implements Serializable {

    /**
     * The Access Token
     * @var string
     */
    protected $accessToken = null;

    /**
     * Refresh Token
     * @var string
     */
    protected $refreshToken = null;

    /**
     * Access Token Expiry
     * @var DateTime $expiresAt
     */
    protected $expiresAt = null;

    public function isAlive() {
        $now = new DateTime();
        return ($this->expiresAt->getTimestamp() > $now->getTimestamp());
    }

    public function refreshToken() {
        return $this->refreshToken;
    }

    public function __construct($accessToken = null, $refreshToken = null, $expiresAt = 0) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = new DateTime();
        $this->expiresAt->setTimestamp($expiresAt);
    }

    /**
     * Return Access Token as String
     * @return string
     */
    public function __toString() {
        return $this->isAlive() ? $this->accessToken : null;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize() {
        $expires = 0;
        if ($this->expiresAt instanceof DateTime) {
            $expires = $this->expiresAt->getTimestamp();
        }
        return json_encode(array('access_token' => $this->accessToken, 'refresh_token' => $this->refreshToken, 'expires' => $expires));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @throws DeepSeaException
     * @return void
     */
    public function unserialize($serialized) {
        $obj = json_decode($serialized);
        if ($obj === null) {
            throw DeepSeaException::create('Failed to Unserialize Object', 1004);
        }
        $this->__construct($obj->access_token, $obj->refresh_token, $obj->expires);
    }
}