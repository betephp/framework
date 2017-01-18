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
        'Make\\Web',
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

        echo $this->runAction($pathInfo, $params);
    }

    public function runAction($pathInfo, $params = [])
    {
        $action = explode('/', $pathInfo);

        if (count($action) > 2) {
            throw new WebException("The pathinfo only support two level.");
        }
        
        foreach ($action as $key => $value) {
            $action[$key] = ucfirst($value);
        }
        if (in_array(implode('\\', $action), $this->internalActions)) {
            $class = 'Bete\\Console\\Action\\' . implode('\\', $action);
        } else {
            $class = 'App\\Console\\' . implode('\\', $action);
        }

        try {
            $action = $this->app->make($class);
        } catch (\Exception $e) {
            throw new ConsoleException("Not Found");
        }

        return $action->run($this->app->request);
    }

}
