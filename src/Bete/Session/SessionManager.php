<?php

namespace Bete\Session;

class SessionManager
{
    protected $app;

    protected $config;

    protected $session;

    protected $started = false;

    public $flashParam = '__flash';

    public function __construct($app)
    {
        $this->app = $app;

        $this->init();
    }

    public function init()
    {
        register_shutdown_function([$this, 'close']);

        if ($this->isActive()) {
            $this->updateFlashes();
        }
    }

    public function open()
    {
        if ($this->isActive()) {
            return;
        }

        $this->setSessionHandler();

        @session_start();

        if ($this->isActive()) {
            $this->updateFlashes();
        }
    }

    public function isActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function close()
    {
        if ($this->isActive()) {
            @session_write_close();
        }
    }

    protected function updateFlashes()
    {
        $flashes = $this->get($this->flashParam, []);
        if (is_array($flashes)) {
            foreach ($flashes as $key => $count) {
                if ($count > 0) {
                    unset($flashes[$key], $_SESSION[$key]);
                } elseif ($count == 0) {
                    $flashes[$key]++;
                }
            }
            $_SESSION[$this->flashParam] = $flashes;
        } else {
            unset($_SESSION[$this->flashParam]);
        }
    }

    public function setSessionHandler()
    {
        $building = "build" . ucfirst($this->getDriver()) . "Handler";
        $handler = $this->$building();
        session_set_save_handler($handler, true);
    }

    public function buildFileHandler()
    {
        $path = $this->app['config']['session.file.path'];
        
        return new FileHandler($path, $this->getLifetime());
    }

    public function buildDatabaseHandler()
    {
        $database = $this->app['config']['session.database'];

        return new DatabaseHandler($database['connection'],
            $database['table'], $this->getLifetime());
    }

    public function buildRedisHandler()
    {
        $redis = $this->app['config']['session.redis'];

        return new RedisHandler($redis['connection'], 
            $redis['prefix'], $this->getLifetime());
    }

    public function getDriver()
    {
        return $this->app['config']['session.driver'];
    }

    public function getLifetime()
    {
        return $this->app['config']['session.lifetime'];
    }

    public function getSession()
    {
        if ($this->session === null) {
            $this->session = new Session();
        }

        $session = $this->session;

        return $session;
    }


    public function set($key, $value)
    {
        $this->open();

        $_SESSION[$key] = $value;
    }

    public function get($key = null, $defaultValue = null)
    {
        $this->open();
        
        if ($key === null) {
            return $_SESSION;
        }

        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    public function has($key)
    {
        $this->open();
        
        return isset($_SESSION[$key]);
    }

    public function push($key, $value)
    {
        $this->open();
        
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } else {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } else {
                $_SESSION[$key] = [$_SESSION[$key], $value];
            }
        }
    }

    public function pull($key, $defaultValue = null)
    {
        $this->open();
        
        if (empty($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            return $defaultValue;
        } else {
            $value = array_pop($_SESSION[$key]);
            return ($value !== null) ? $value : $defaultValue;
        }
    }

    public function remove($key)
    {
        $this->open();
        
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);

            return $value;
        } else {
            return null;
        }
    }

    public function clear()
    {
        $this->open();
        
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    public function setFlash($key, $value)
    {
        $flashes = $this->get($this->flashParam, []);
        $flashes[$key] = -1;
        $_SESSION[$key] = $value;
        $_SESSION[$this->flashParam] = $flashes;
    }

    public function getFlash($key = null, $defaultValue = null)
    {
        $flashes = $this->get($this->flashParam, []);

        if ($key === null) {
            $all = [];
            foreach (array_keys($flashes) as $key) {
                if (array_key_exists($key, $_SESSION)) {
                    $all[$key] = $_SESSION[$key];
                    if ($flashes[$key] < 0) {
                        $flashes[$key] = 1;
                    }
                } else {
                    unset($flashes[$key]);
                }
            }

            $_SESSION[$this->flashParam] = $flashes;

            return $all;
        }


        if (isset($flashes[$key])) {
            $value = $this->get($key, $defaultValue);

            if ($flashes[$key] < 0) {
                $flashes[$key] = 1;
                $_SESSION[$this->flashParam] = $flashes;
            }

            return $value;
        } else {
            return $defaultValue;
        }
    }

    public function hasFlash($key)
    {
        return $this->getFlash($key) !== null;
    }
    
    public function pushFlash($key, $value)
    {
        $flashes = $this->get($this->flashParam, []);
        $flashes[$key] = -1;
        $_SESSION[$this->flashParam] = $flashes;

        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } else {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } else {
                $_SESSION[$key] = [$_SESSION[$key], $value];
            }
        }
    }

    public function pullFlash($key, $defaultValue = null)
    {
        $flashes = $this->get($this->flashParam, []);

        if (!is_array($this->getFlash($key))) {
            return $defaultValue;
        } else {
            $value = array_pop($_SESSION[$key]);
            return ($value !== null) ? $value : $defaultValue;
        }

    }

    public function removeFlash($key)
    {
        $flashes = $this->get($this->flashParam, []);

        $value = isset($_SESSION[$key], $flashes[$key]) ? $_SESSION[$key] : null;
        unset($flashes[$key], $_SESSION[$key]);
        $_SESSION[$this->flashParam] = $flashes;

        return $value;
    }

    public function clearFlash()
    {
        $flashes = $this->get($this->flashParam, []);
        foreach (array_keys($flashes) as $key) {
            unset($_SESSION[$key]);
        }
        unset($_SESSION[$this->flashParam]);
    }

}
