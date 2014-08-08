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

    const TOKEN_STORAGE = 'AccessToken';

    public function setAccessToken(AccessToken $accessToken = null) {
        $state = Session::getInstance();
        $state->{DeepSeaSession::TOKEN_STORAGE} = $accessToken;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken() {
        $state = Session::getInstance();
        return $state->{DeepSeaSession::TOKEN_STORAGE};
    }

    public function __construct(AccessToken $accessToken = null) {
        $this->setAccessToken($accessToken);
    }



} 