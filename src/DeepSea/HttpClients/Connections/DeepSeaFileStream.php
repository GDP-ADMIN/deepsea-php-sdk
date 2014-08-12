<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/11/14
 * Time: 3:45 PM
 * GDP Venture © 2014
 */

namespace DeepSea\HttpClients\Connections;


class DeepSeaFileStream {

    protected $options = array();
    protected $context;
    protected $url;

    public function setOpt($option, $value) {
        $this->options['http'][$option] = $value;
    }

    public function setOptArray($options) {
        $this->options['http'] = array_merge($this->options['http'], $options);
    }

    public function __construct() {
        $this->options = array(
            'http' => array(
                'timeout' => 60,
                'ignore_errors' => true
            ),
            'ssl' => array(
                'verify_peer' => false,
                'cafile' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'deepsea.crt',
            ),
        );
    }

    public function open($url, $options = array()) {
        $this->setOptArray($options);
        $this->url = $url;
        $this->context = stream_context_create($this->options);
    }

    public function exec() {
        return fopen($this->url, 'rb', false, $this->context);
    }

} 