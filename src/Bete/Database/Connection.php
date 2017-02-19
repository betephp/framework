<?php

namespace Bete\Database;

use PDO;
use Closure;
use Exception;
use Bete\Support\Str;
use Bete\Support\Arr;
use Bete\Exception\QueryException;

class Connection
{

    protected $pdo;

    protected $readPdo;

    protected $config;

    protected $database;

    protected $tablePrefix;

    protected $reconnector;

    protected $transactions = 0;

    protected $fetchMode = PDO::FETCH_OBJ;

    protected $fetchArgument;

    public function __construct($pdo, array $config = [])
    {
        $this->pdo = $pdo;

        $this->config = $config;

        if (isset($config['database'])) {
            $this->database = $config['database'];
        }

        if (isset($config['tablePrefix'])) {
            $this->tablePrefix = $config['tablePrefix'];
        }
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function table($table)
    {
        return $this->query()->from($table);
    }

    public function query()
    {
        return new QueryBuilder($this);
    }

    public function raw($value)
    {
        return new SqlExpression($value);
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, 
            function($me, $query, $bindings) use ($useReadPdo) {
            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

            $me->bindValues($statement, $bindings);

            $statement->execute();

            $fetchMode = $me->getFetchMode();
            $fetchArgument = $me->getFetchArgument();

            if (isset($fetchArgument)) {
                return $statement->fetchAll($fetchMode, $fetchArgument);
            } else {
                return $statement->fetchAll($fetchMode);
            }
        });
    }

    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    public function getFetchArgument()
    {
        return $this->fetchArgument;
    }

    public function cursor($query, $bindings = [], $useReadPdo = true)
    {

    }

    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $res = $statement->execute();
            return $res;
        });
    }

    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            $statement = $me->getPdo()->prepare($query);

            $this->bindValues($statement, $bindings);

            $statement->execute();

            return $statement->rowCount();
        });
    }

    public function run($query, $bindings, Closure $callback)
    {
        $this->reconnectIfLostConnection();

        try {
            $result = $this->runQueryCallBack($query, $bindings, $callback);
        } catch (QueryException $e) {
            if ($this->transactions >= 1) {
                throw $e;
            }

            $result = $this->tryAgainIfLostConnection(
                $e, $query, $bindings, $callback);
        }

        return $result;
    }

    protected function tryAgainIfLostConnection(
        QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    protected function runQueryCallBack($query, $bindings, Closure $callback)
    {
        try {
            $result = $callback($this, $query, $bindings);
        } catch (Exception $e) {
            throw new QueryException($query, $bindings, $e);
        }

        return $result;
    }

    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new DatabaseException('Lost all the connection.');
    }

    protected function reconnectIfLostConnection()
    {
    }

    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }

    public function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    public function setPdo($pdo)
    {
        if ($this->transactions >= 1) {
            throw new DatabaseException("Can not switch PDO in transaction.");
        }

        $this->pdo = $pdo;

        return $this;
    }

    public function getReadPdo()
    {
        if ($this->transactions >= 1) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ? $this->readPdo : $this->getPdo();
    }

    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($i = 1; $i <= $attempts; $i++) {
            $this->beginTransaction();
            try {
                $result = $callback($this);

                $this->commit();
            } catch (Exception $e) {
                $this->rollBack();

                if ($this->causedByDeadlock($e) && $i < $attempts) {
                    continue;
                }

                throw $e;
            } catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }

            return $result;
        }
    }

    public function beginTransaction()
    {
        $this->transactions++;

        if ($this->transactions == 1) {
            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                $this->transactions--;

                throw $e;
            }
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->rollBack();
        }

        $this->transactions = max(0, $this->transactions - 1);
    }

    public function getName()
    {
        return $this->getConfig('name');
    }

    public function getConfig($option)
    {
        return Arr::get($this->config, $option);
    }

    protected function causedByDeadlock(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
        ]);
    }

    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
        ]);
    }

}
