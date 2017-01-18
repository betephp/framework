<?php

namespace Bete\Cookie;

use Exception;

class Cookie
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function get($name, $defaultValue = null)
    {
        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;

        if (is_null($value)) {
            return $defaultValue;
        }

        try {
            $value = $this->app->encrypt->decrypt($value);
        } catch (Exception $e) {
            $value = $defaultValue;
        }

        return $value;
    }

    public function set($name, $value = '', $expire = 7200)
    {
        if ($expire < (86400 * 30)) {
            $expire += time();
        }

        $value = $this->app->encrypt->encrypt($value);

        return setcookie($name, $value, $expire, '/', '', false, true);
    }
}
