<?php

namespace Bete\Foundation;

abstract class Component
{
    protected $app;

    protected $lazy = false;

    public function __construct($app)
    {
        $this->app = $app;
    }

    abstract public function register();

    abstract public function names();

    public function isLazy()
    {
        return $this->lazy;
    }

}
