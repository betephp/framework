<?php

namespace App\Middleware;

use Bete\Foundation\Middleware;

class {{middleware}} extends Middleware
{
    public function beforeAction($action)
    {
        return true;
    }

    public function afterAction($action, $result)
    {
        return $result;
    }
}
