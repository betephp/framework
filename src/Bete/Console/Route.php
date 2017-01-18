<?php

namespace Bete\Console;

use Bete\Exception\Exception;

class Route
{
    protected $app;

    protected $request;

    public function __construct($app, $request)
    {
        $this->app = $app;
        $this->request = $request;
    }
    
    public function resolve()
    {
        list($pathInfo, $args) = $this->request->parseArgs();

        if ($pathInfo === '') {
            $pathInfo = 'help';
        }

        $pathInfo = str_replace(':', '/', $pathInfo);
        if (strpos($pathInfo, '/') === false) {
            $pathInfo .= '/index';
        }

        return [$pathInfo, $args];
    }

}
