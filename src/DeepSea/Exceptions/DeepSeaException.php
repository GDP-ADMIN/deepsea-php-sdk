<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:06 AM
 * GDP Venture © 2014
 */

namespace DeepSea\Exceptions;

use Exception;
use ReflectionClass;

class DeepSeaException extends Exception {

    public function __construct($message = "", $code = 0) {
        parent::__construct($message, $code);
    }

    /**
     * @param string $message
     * @param int $code
     * @return DeepSeaException
     */
    public static function create($message = "", $code = 0) {
        $class = new ReflectionClass(get_called_class());
        return $class->newInstance($message, $code);
    }
}