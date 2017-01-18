<?php

namespace Bete\Database;

use Bete\Foundation\Application;
use Bete\Exception\ConfigException;
use Bete\Database\Connector\MysqlConnector;

class ConnectionFactory
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function make(array $config, $name = null)
    {
        // $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    protected function createSingleConnection(array $config)
    {
        $connector = $this->createPdoResolver($config);

        return $this->createConnection($connector, $config); 
    }

    protected function createReadWriteConnection($config)
    {
        $connection = $this->createSingleConnection(
            $this->getWriteConfig($config));

        return $connection->setReadPdo($this->createReadPdo($config));
    }

    protected function createReadPdo(array $config)
    {
        return $this->createPdoResolver($this->getReadConfig($config));
    }

    protected function getReadConfig($config)
    {
        $readConfig = $this->getReadWriteConfig($config, 'read');
    }

    protected function getWriteConfig($config)
    {
        $readConfig = $this->getReadWriteConfig($config, 'read');
    }

    protected function getReadWriteConfig(array $config, $type)
    {
        if (isset($config[$type][0])) {
            return $config[$type][array_rand($config[$type])];
        }

        $readWriteconfig = $config[$type];

        $readWriteconfig = array_merge($config, $readWriteconfig);

        unset($readWriteconfig['read']);
        unset($readWriteconfig['write']);

        return $readWriteconfig;
    }

    protected function createPdoResolver(array $config)
    {
        return $this->createConnector($config)->connect();
    }

    protected function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new ConfigException('A database driver mus be specified.');
        }

        $driver = $config['driver'];

        switch ($driver) {
            case 'mysql':
                return new MysqlConnector($config);
        }

        throw new ConfigException("Unsupported database driver: {$driver}");
    }

    protected function createConnection($connector, array $config = [])
    {
        return new Connection($connector, $config);
    }
}
