<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/14/14
 * Time: 1:09 PM
 * GDP Venture © 2014
 */

// Vendor Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Test Namespace loader
spl_autoload_register(function($class) {
    $baseNamespace = 'DeepSea\\Test';
   if (strpos(strtolower($class), strtolower($baseNamespace)) !== false) {
       $className = substr($class, strlen($baseNamespace));
       $filePath = __DIR__ . $className . '.php';
       if (file_exists($filePath)) {
           require_once $filePath;
       }
   }
});

// Real Class Autoload
require_once __DIR__ . '/../autoload.php';
