<?php

namespace Bete\Http;

use ReflectionClass;
use Bete\Foundation\Application;
use Bete\Exception\HttpNotFoundException;
use Bete\Http\Route;

class Core
{
    protected $app;

    protected $route;

    protected $bootstrappers = [
        'Bete\Bootstrap\LoadConfiguration',
        'Bete\Bootstrap\ConfigureLog',
        'Bete\Bootstrap\HandleException',
        'Bete\Bootstrap\RegisterHttpComponents',
        'Bete\Bootstrap\BootComponents',
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
        $route = $this->app->route->resolve();

        list($pathInfo, $params) = $route;

        $_GET = $params + $_GET;

        echo $this->handleRequest($pathInfo, $params);

        return true;
    }

    public function handleRequest($route, $params = [])
    {
        $pat = '/^[a-zA-Z_][a-zA-Z0-9_]*(\/([a-zA-Z_][a-zA-Z0-9-_]*)?)?$/';
        if (!preg_match($pat, $route)) {
            throw new HttpNotFoundException(
                "The controller/action should follow class convention.");
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

        $name = ucfirst($id) . 'Controller';
        $class = 'App\\Http\\' . $name;
        if (!class_exists($class)) {
            throw new HttpNotFoundException("The {$name} doesn't exists");
        }

        if (!is_subclass_of($class, 'Bete\Http\Controller')) {
            throw new HttpNotFoundException(
                "The controller must extends from Bete\Http\Controller");
        }

        $instance = $this->app->make($class, [$this->app, $id]);
        return [$instance, $route];
    }
}
