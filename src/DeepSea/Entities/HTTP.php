<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:08 AM
 * GDP Venture © 2014
 */

namespace DeepSea\Entities;

class HTTP {
    // Method
    const GET    = 'GET';
    const POST   = 'POST';
    const DELETE = 'DELETE';
    const PUT    = 'PUT';

    // Code
    const OK              = 200;
    const CREATED         = 201;
    const ACCEPTED        = 202;
    const NO_CONTENT      = 204;
    const BAD_REQUEST     = 400;
    const UNAUTHORIZED    = 401;
    const FORBIDDEN       = 403;
    const NOT_FOUND       = 403;
    const NOT_ALLOWED     = 405;
    const GONE            = 420;
    const ERROR           = 500;
    const NOT_IMPLEMENTED = 501;
    const UNAVAILABLE     = 503;
} 