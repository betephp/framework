<?php

namespace Bete\Redis;

use Redis;
use Bete\Exception\ConfigException;
use Bete\Support\Arr;

class RedisManager
{

    protected $app;

    protected $connections;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function connection($name = null)
    {
        $name = $this->parseConnectionName($name);

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    public function conn($name = null)
    {
        return $this->connection($name);
    }

    public function parseConnectionName($name = null)
    {
        $name = $name ? $name : $this->getDefaultConnection();
    }

    protected function createConnection($name = null)
    {
        $config = $this->getConfig($name);

        $redis = new Redis();

        if (empty($config['socket'])) {
            $timeout = isset($config['timeout']) ? $config['timeout'] : 0;
            $redis->connect($config['host'], $config['port'], $timeout);
        } else {
            $redis->connect($config['socket']);
        }

        if (!empty($config['prefix'])) {
            $redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
        }

        if (isset($config['password']) && $config['password'] !== false) {
            if (!$redis->auth($config['password'])) {
                throw new ConfigException('Redis auth failed.');
            }
        }

        if (isset($config['database']) && $config['database'] !== 0) {
            if (!$redis->select($config['database'])) {
                throw new ConfigException('Can not select database.');
            }
        }

        return $redis;
    }

    protected function getConfig($name = null)
    {
        $name = $name ? $name : $this->getDefaultConnection();

        $connections = $this->app->config['redis.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new ConfigException("Redis [$name] not configured.");
        }

        return $config;
    }

    public function getDefaultConnection()
    {
        return $this->app['config']['redis.default'];
    }

    public function __call($method, $parameters)
    {
        $connection = $this->connection();
        return call_user_func_array([$connection, $method], $parameters);
    }
}
