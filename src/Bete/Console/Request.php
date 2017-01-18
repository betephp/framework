<?php

namespace Bete\Console;

use Bete\Foundation\Application;

class Request
{

    protected $app;

    protected $args;

    protected $options = [];

    protected $params = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getArgs()
    {
        if ($this->args === null) {
            if (isset($_SERVER['argv'])) {
                $this->args = $_SERVER['argv'];
                array_shift($this->args);
            } else {
                $this->args = [];
            }
        }

        return $this->args;
    }

    public function parseArgs()
    {
        $args = $this->getArgs();
        if (isset($args[0])) {
            $route = $args[0];
            array_shift($args);
        } else {
            $route = '';
        }
        
        $num = 1;
        foreach ($args as $arg) {
            if (preg_match('/^--(\w+)(?:=(.*))?$/', $arg, $matches)) {
                $name = $matches[1];
                $this->options[$name] = isset($matches[2]) ?
                    $matches[2] : true;
            } else {
                $this->params[$num] = $arg;
                $num++;
            }
        }

        return [$route, $this->params];
    }

    public function param($num = null, $defaultValue = null)
    {
        if ($num === null) {
            return $this->params;
        }

        return isset($this->params[$num]) ? $this->params[$num]
            : $defaultValue;
    }
    
    public function option($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->options;
        } else {
            return isset($this->options[$name]) ?
                $this->options[$name] : $defaultValue;
        }
    }

}

