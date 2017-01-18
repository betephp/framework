<?php

namespace Bete\Redis;

class Redis
{

    public static function __callStatic($method, $args)
    {
        $instance = app()->redis;

        return call_user_func_array([$instance, $method], $args);
    }

}
