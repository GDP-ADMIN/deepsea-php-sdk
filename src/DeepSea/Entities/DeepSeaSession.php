<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:16 AM
 * GDP Venture © 2014
 */

namespace DeepSea\Entities;


class DeepSeaSession {

    const TOKEN_STORAGE = 'ACCESS_TOKEN';
    const STATE_STORAGE = 'OAUTH_STATE';

    public static function setState($state) {
        $deepseaState = Session::getInstance();
        $deepseaState->{self::STATE_STORAGE} = $state;
    }

    public static function loadState() {
        $state = Session::getInstance();
        return $state->{self::STATE_STORAGE};
    }

    /**
     * @return AccessToken
     */
    public static function availableAccessToken() {
        $state = Session::getInstance();
        return $state->{self::TOKEN_STORAGE};
    }

    public function setAccessToken(AccessToken $accessToken = null) {
        $state = Session::getInstance();
        $state->{self::TOKEN_STORAGE} = $accessToken;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken() {
        $state = Session::getInstance();
        return $state->{self::TOKEN_STORAGE};
    }

    public function __construct(AccessToken $accessToken = null) {
        $this->setAccessToken($accessToken);
    }



} 