<?php
/**
 * Created by PhpStorm.
 * User: glenn.kristanto
 * Date: 8/8/14
 * Time: 9:24 AM
 * GDP Venture © 2014
 */

namespace DeepSea\Entities;


class Session {
    const SESSION_PREFIX = 'DS_SESSION_';
    const SESSION_STARTED = true;
    const SESSION_NOT_STARTED = false;

    // The state of the session
    private $sessionState = self::SESSION_NOT_STARTED;

    // THE only instance of the class
    private static $instance;


    private function __construct() {
    }

    /**
     * Returns THE instance of 'Session'.
     * The session is automatically initialized if it wasn't.
     *
     * @return object
     **/

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        self::$instance->startSession();
        return self::$instance;
    }


    /**
     * (Re)starts the session.
     *
     * @return bool true if the session has been initialized, else false.
     **/

    public function startSession() {
        if ($this->sessionState == self::SESSION_NOT_STARTED) {
            $this->sessionState = session_start();
        }

        return $this->sessionState;
    }

    /**
     * Stores datas in the session.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value) {
        $_SESSION[self::SESSION_PREFIX . $key] = $value;
    }

    /**
     * Gets datas from the session.
     *
     * @param $key
     * @return mixed|null
     */
    public function __get($key) {
        return (isset($_SESSION[self::SESSION_PREFIX . $key])) ? $_SESSION[self::SESSION_PREFIX . $key] : null;
    }


    public function __isset($key) {
        return isset($_SESSION[self::SESSION_PREFIX . $key]);
    }


    public function __unset($key) {
        unset($_SESSION[self::SESSION_PREFIX . $key]);
    }


    /**
     * Destroys the current session.
     *
     * @return bool true is session has been deleted, else false.
     **/
    public function destroy() {
        if ($this->sessionState == self::SESSION_STARTED) {
            $this->sessionState = !session_destroy();
            unset($_SESSION);
            return !$this->sessionState;
        }

        return false;
    }
}
