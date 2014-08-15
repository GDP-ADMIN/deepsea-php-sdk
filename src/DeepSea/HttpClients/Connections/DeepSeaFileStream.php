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

    protected $options = array(
        'http' => array(
            'timeout' => 60,
            'ignore_errors' => true
        ),
        'ssl' => array(
            'verify_peer' => true,
        )
    );
    protected $resource;
    protected $url;

    public function setOpt($option, $value) {
        $this->options['http'][$option] = $value;
    }

    public function setOptArray($options) {
        $this->options['http'] = array_merge($this->options['http'], $options);
    }

    public function __construct() {
        $this->options['ssl']['cafile'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ca_bundle.cer';
    }

    public function open($url, $options = array()) {
        $this->setOptArray($options);
        $this->url = $url;
        $context = stream_context_create($this->options);
        $this->resource = fopen($this->url, 'rb', false, $context);
    }

    public function exec() {
        $metaData = stream_get_meta_data($this->resource);
        return array(
            'header' => $metaData['wrapper_data'],
            'content' => stream_get_contents($this->resource),
        );
    }

    public function close() {
        fclose($this->resource);
    }

} 