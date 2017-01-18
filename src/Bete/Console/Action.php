<?php

namespace Bete\Console;

use Bete\Foundation\Application;
use Bete\Console\Request;

abstract class Action
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract function run(Request $request);
}
