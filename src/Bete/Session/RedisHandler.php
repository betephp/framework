<?php

namespace Bete\Session;

use SessionHandlerInterface;

class RedisHandler implements SessionHandlerInterface
{
    protected $connection;

    protected $prefix;

    protected $lifetime;

    public function __construct($connection, $prefix, $lifetime)
    {
        $this->connection = $connection;
        $this->prefix = $prefix;
        $this->lifetime = $lifetime;
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function getKey($sessionId)
    {
        return $this->prefix . $sessionId;
    }

    public function read($sessionId)
    {
        $key = $this->getKey($sessionId);

        $value = app()->redis->conn($this->connection)->get($key);

        return ($value) ? $value : '';
    }

    public function write($sessionId, $data)
    {
        $key = $this->getKey($sessionId);

        app()->redis->conn($this->connection)
            ->setEx($key, $this->lifetime, $data);
    }

    public function destroy($sessionId)
    {
        $key = $this->getKey($sessionId);

        app()->redis->conn($this->connection)->delete($key);
    }

    public function gc($lifetime)
    {
        return true;
    }
}
