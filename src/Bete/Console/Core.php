<?php

namespace Bete\Console;

use Exception;
use ReflectionMethod;
use Bete\Foundation\Application;
use Bete\Console\Route;
use Bete\Exception\ConsoleException;

class Core
{

    protected $app;

    protected $bootstrappers = [
        'Bete\Bootstrap\LoadConfiguration',
        'Bete\Bootstrap\ConfigureLog',
        'Bete\Bootstrap\HandleException',
        'Bete\Bootstrap\RegisterConsoleComponents',
        'Bete\Bootstrap\BootComponents',
    ];

    protected $internalActions = [
        'Make\\Middleware',
        'Make\\Http',
        'Make\\Console',
        'Make\\Model',
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        if (!$this->app->isBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    public function bootstrappers()
    {
        return $this->bootstrappers;
    }

    public function run()
    {
        $this->bootstrap();

        return $this->handle();
    }

    public function handle()
    {
        list($pathInfo, $params) = $this->app->route->resolve();

        echo $this->handleRequest($pathInfo, $params);
    }

    public function handleRequest($route, $params = [])
    {
        if (count(explode('/', $route)) > 2) {
            throw new ConsoleException("The pathinfo only support two level.");
        }

        $pat = '/^[a-zA-Z][a-zA-Z0-9_]*(\/[a-zA-Z][a-zA-Z0-9_]*)?$/';
        if (!preg_match($pat, $route)) {
            throw new ConsoleException("The url format is illegal.");
        }

        $info = $this->createController($route);

        list($controller, $actionId) = $info;
        
        $result = $controller->run($actionId, $params);

        return $result;
    }

    public function createController($route)
    {
        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        if (strtolower($id) === 'make') {
            $class = 'Bete\\Console\\MakeController';
        } else {
            $class = 'App\\Console\\' . ucfirst($id . 'Controller');
        }
        
        if (!class_exists($class)) {
            throw new Exception("{$class} doesn't exists.");
        }
        
        if (is_subclass_of($class, 'Bete\\Console\\Controller')) {
            $instance = $this->app->make($class, [$this->app, $id]);
            return [$instance, $route];
        }

        throw new ConsoleException(
            "Controller must extend from Bete\Http\Controller.");
    }

}
