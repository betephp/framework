<?php

namespace Bete\Database;

class DB
{

    public static function __callStatic($method, $args)
    {
        $instance = app()->db;

        return call_user_func_array([$instance, $method], $args);
    }

}
