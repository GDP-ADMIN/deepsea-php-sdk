<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 11:12 AM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients;


class DeepSeaHttpResponse {
    const CODE  = 'code';
    const TOKEN = 'token';

    /**
     * Public for Backward Compatibility
     *
     * TODO: Make protected, deprecated direct access;
     */
    public $header;
    public $content;

    /**
     * @return object
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * @return object
     */
    public function getContent() {
        return $this->content;
    }

    public function __construct($header, $content) {
        $this->header  = json_decode($header);
        $this->content = json_decode($content);
    }
} 