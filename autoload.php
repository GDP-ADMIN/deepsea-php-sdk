<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/14/14
 * Time: 12:57 PM
 * GDP Venture © 2014
 */

spl_autoload_register(function ($class) {
   $file = __DIR__ . DIRECTORY_SEPARATOR . $class . ".php";
   if (file_exists($file)) { require_once $file; }
});