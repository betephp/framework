<?php

namespace Bete\Web;

use Bete\Foundation\Application;
use Bete\Exception\Exception;
use Bete\Exception\WebException;
use Bete\Web\Route;
use Bete\Web\Action;
use ReflectionClass;

class Core
{
    protected $app;

    protected $route;

    protected $bootstrappers = [
        'Bete\Bootstrap\LoadConfiguration',
        'Bete\Bootstrap\ConfigureLog',
        'Bete\Bootstrap\HandleException',
        'Bete\Bootstrap\RegisterWebComponents',
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

    public function createController($route)
    {
        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        $class = 'App\\Web\\' . ucfirst($id . 'Controller');
        if (is_subclass_of($class, 'Bete\Web\Controller')) {
            $instance = $this->app->make($class, [$this->app, $id]);
            return [$instance, $route];
        } else {
            throw new WebException(
                "Controller must extend from Bete\Web\Controller.");
        }
    }

    public function handleRequest($route, $params = [])
    {
        if (count(explode('/', $route)) > 2) {
            throw new WebException("The pathinfo only support two level.");
        }

        $info = $this->createController($route);

        list($controller, $actionId) = $info;
        
        $result = $controller->run($actionId, $params);

        return $result;
    }


}
