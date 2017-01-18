<?php

namespace Bete\Web;

use Bete\Foundation\Application;
use Bete\Exception\Exception;
use Bete\Exception\WebException;
use Bete\Web\Route;
use Bete\Web\Action;

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

        echo $this->runAction($pathInfo, $params);

        return true;
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
        $action = 'App\\Web\\' . implode('\\', $action);

        $globalMiddlewares = $this->getMiddlewares($pathInfo);

        try {
            $action = $this->app->make($action);
        } catch (\Exception $e) {
            throw new WebException("Not Found");
        }

        if ($action->renderType === Action::TYPE_JSON) {
            $this->app->request->setAcceptsJson(true);
        }

        $actionMiddlewares = $action->middlewares();

        $middlewares = array_unique(
            array_merge($globalMiddlewares, $actionMiddlewares));

        $middlewares = $this->makeMiddlewares($middlewares);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $middleware->beforeAction($action);
            }
        }

        $result = $action->run($this->app->request);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $result = $middleware->afterAction($action, $result);
            }
        }

        return $result;
    }

    public function getMiddlewares($pathInfo)
    {
        $allMiddlewares = $this->app->config['middleware'];
        $middlewares = [];
        foreach ($allMiddlewares as $middleware => $rule) {
            if ($this->determineRule($pathInfo, $rule)) {
                $middlewares[] = $middleware;
            }
        }    
        return $middlewares;
    }

    public function makeMiddlewares(array $middlewares)
    {
        $instances = [];
        foreach ($middlewares as $middleware) {
            $explode = explode(':', $middleware);
            $instance = $this->app->make($explode[0]);

            if (isset($explode[1]) && !empty($explode[1])) {
                $params = explode(',', $explode[1]);
                $instance->setParams($params);
            }

            $instances[] = $instance;
        }
        return $instances;
    }

    public function determineRule($pathInfo, $rule)
    {
        if ($rule === '*') {
            return true;
        } elseif (is_string($rule) 
            && preg_match('/^(\w+)(\/[*,\w+]+)?$/', $rule, $matches)) {
            $pathInfo = explode('/', $pathInfo);
            if (!isset($matches[2])) {
                $actions = '*';
            } else {
                $actions = trim($matches[2], '/,');
            }
            $actions = explode(',', $actions);

            if ($matches[1] === $pathInfo[0] && (in_array('*', $actions) 
                || in_array($pathInfo[1], $actions))) {
                return true;
            } else {
                return false;
            }
        } elseif (is_array($rule)) {
            foreach ($rule as $r) {
                if ($this->determineRule($pathInfo, $r) === true) {
                    return true;
                }
            }
            return false;
        } else {
            throw new Exception("Can not determine rule: {$rule}.");
        }
    }
}
