<?php

namespace Bete\Database;

use Bete\Foundation\Application;
use Bete\Support\Arr;
use Bete\Database\ConnectionFactory;

class DatabaseManager
{

    protected $app;

    protected $factory;

    protected $connections;

    public function __construct(Application $app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    public function connection($name = null)
    {
        $name = $this->getConnectionName($name);

        if (!isset($this->connections[$name])) {
            $connection = $this->makeConnection($name);

            $this->connections[$name] = $this->prepare($connection);
        }

        return $this->connections[$name];
    }

    public function getConnectionName($name)
    {
        $name = $name ? $name : $this->getDefaultConnection();
    }

    protected function makeConnection($name)
    {
        $config = $this->getConfig($name);

        return $this->factory->make($config, $name);        
    }

    protected function prepare(Connection $connection)
    {
        $connection->setFetchMode($this->app['config']['database.fetch']);

        $connection->setReconnector(function ($connection) {
            $this->reconnect($connection->getName());
        });

        return $connection;
    }

    protected function setPdoForType(Connection $connection, $type = null)
    {
        if ($type == 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type == 'write') {
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    public function reconnect($name = null)
    {
        $name = $this->getConnectionName($name);
        $this->disconnect($name);

        if (!isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
            ->setPdo($fresh->getPdo())
            ->setReadPdo($fresh->getReadPdo());
    }

    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ? $name : $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    protected function getConfig($name)
    {
        $name = $name ? $name : $this->getDefaultConnection();

        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new ConfigException("Database [$name] not configured.");
        }

        return $config;
    }

    public function getDefaultConnection()
    {
        return $this->app['config']['database.default'];
    }

    public function setDefaultConnection($name)
    {
        $this->app['config']['database.default'] = $name;
    }

    public function supportedDrivers()
    {
        // return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
        return ['mysql'];
    }

    public function availableDrivers()
    {
        return array_intersect($this->supportedDrivers(), 
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers()));
    }

    public function getConnections()
    {
        return $this->connections;
    }

    public function __call($method, $parameters)
    {
        $connection = $this->connection();
        return call_user_func_array([$connection, $method], $parameters);
    }

}
