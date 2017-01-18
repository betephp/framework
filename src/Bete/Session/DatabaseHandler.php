<?php

namespace Bete\Session;

use SessionHandlerInterface;

class DatabaseHandler implements SessionHandlerInterface
{
    protected $connection;

    protected $table;

    protected $lifetime;

    public function __construct($connection, $table, $lifetime)
    {
        $this->connection = $connection;
        $this->table = $table;
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

    public function read($sessionId)
    {
        $res = app()->db->connection($this->connection)->table($this->table)->
            where('id', $sessionId)->first();

        if ($res && isset($res->update_time) 
            && (time() - $res->update_time <= $this->lifetime)) {
            return base64_decode($res->data);
        }

        return '';
    }

    public function write($sessionId, $data)
    {
        $table = app()->db->connection($this->connection)->table($this->table);

        $row = $table->where('id', $sessionId)->first();

        $data = base64_encode($data);

        if ($row) {
            $row = [
                'data' => $data,
                'update_time' => time(),
            ];
            $table->where('id', $sessionId)->update($row);
        } else {
            $row = [
                'id' => $sessionId,
                'data' => $data,
                'update_time' => time(),
            ];
            $table->insert($row);
        }
    }

    public function destroy($sessionId)
    {
        $table = app()->db->connection($this->connection)->table($this->table);
        $table->where('id', $sessionId)->delete();
    }

    public function gc($lifetime)
    {
        $table = app()->db->connection($this->connection)->table($this->table);

        $table->where('update_time', '<', (time() - $this->lifetime))->delete();
    }
}
