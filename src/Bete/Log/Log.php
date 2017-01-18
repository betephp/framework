<?php

namespace Bete\Log;

class Log
{
    public static function __callStatic($method, $args)
    {
        $instance = app()->log;

        return call_user_func_array([$instance, $method], $args);
    }
}
