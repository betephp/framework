<?php

namespace App\Middleware;

use Bete\Foundation\Middleware;

class {{middleware}} extends Middleware
{
    public function beforeAction()
    {
        return true;
    }

    public function afterAction($result)
    {
        return $result;
    }
}
