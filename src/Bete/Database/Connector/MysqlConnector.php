<?php

namespace Bete\Database\Connector;

use PDO;

class MysqlConnector extends Connector
{
    public function connect()
    {
        $config = $this->config;

        $connection = $this->createConnection();

        if (isset($config['charset']) && !empty($config['charset'])) {
            $charset = $config['charset'];

            $names = "set names '{$charset}'";

            $connection->prepare($names)->execute();
        }

        return $connection;
    }

    public function getDsn()
    {
        return $this->configHasSocket() ? $this->getSocketDsn() 
            : $this->getHostDsn();
    }

    protected function configHasSocket()
    {
        $config = $this->config;

        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    protected function getSocketDsn()
    {
        $config = $this->config;

        return "mysql:unix_socket={$config['unix_socket']};" 
            . "dbname={$config['database']}";
    }

    protected function getHostDsn()
    {
        extract($this->config, EXTR_SKIP);

        return isset($port)
            ? "mysql:host={$host};port={$port};dbname={$database}"
            : "mysql:host={$host};dbname={$database}";
    }
}
