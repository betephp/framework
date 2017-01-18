<?php

namespace Bete\Bootstrap;

use Bete\Foundation\Application;

class BootComponents
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        $this->app->boot();
    }
}
