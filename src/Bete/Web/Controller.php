<?php

namespace Bete\Web;

use Bete\Foundation\Application;
use Bete\Exception\WebException;

class Controller
{
    protected $app;

    public $id;

    public $defaultAction = 'index';

    public $middlewares = [];

    const TYPE_RAW = 'raw';
    const TYPE_JSON = 'json';
    const TYPE_HTML = 'html';

    public $renderType = self::TYPE_HTML;

    public $layout = null;

    public $title = 'Title';

    public function __construct(Application $app, $id)
    {
        $this->app = $app;
        $this->id = $id;
    }

    public function run($actionId, array $params = [])
    {
        if ($actionId === '') {
            $actionId = $this->defaultAction;
        }

        $middlewares = $this->getMiddlewares($this->middlewares, $actionId);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $middleware->beforeAction();
            }
        }

        $result = $this->runAction($actionId);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $result = $middleware->afterAction($result);
            }
        }

        return $result;
    }

    public function runAction($actionId)
    {
        if (preg_match('/^[a-zA-Z0-9-_]+$/', $actionId) 
            && strpos($actionId, '--') === false 
            && trim($actionId, '-') === $actionId) {

            $methodName = 'action' . str_replace(' ', '', 
                ucwords(implode(' ', explode('-', $actionId))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return call_user_func_array(
                        [$this, $methodName], [$this->app->request]);
                }
            }
        }

        throw new WebException("Can not find {$actionId} action.");
    }

    public function getMiddlewares(array $rules, $action)
    {
        $middlewares = [];

        foreach ($rules as $middleware => $rule) {
            if ($this->determineRule($rule, $action)) {
                $middlewares[] = $this->makeMiddleware($middleware);
            }
        }

        return $middlewares;
    }

    public function determineRule($rule, $name)
    {
        $rule = str_replace(' ', '', $rule);

        if ($rule === '*') {
            return true;
        } elseif (preg_match('/^([\w*,]+)(-([\w,]+))?$/', $rule, $matches)) {
            $allows = explode(',', $matches[1]);
            $excepts = isset($matches[3]) ? explode(',', $matches[3]) : [];

            if ((in_array('*', $allows) || in_array($name, $allows))
                && !in_array($name, $excepts)) {
                return true;
            } else {
                return false;
            }
        }

        throw new WebException("Can not determine rule: {$rule}.");
    }

    public function makeMiddleware($middleware)
    {
        $explode = explode(':', $middleware);
        $class = 'App\\Middleware\\' . $explode[0];

        $instance = $this->app->make($class);

        if (isset($explode[1]) && !empty($explode[1])) {
            $params = explode(',', $explode[1]);
            $instance->setParams($params);
        }

        return $instance;
    }

    public function render($view, $data = [])
    {
        return $this->app->view->render($view, $data, $this);
    }

    public function renderPartial($view, $data = [])
    {
        return $this->app->view->renderPartial($view, $data, $this);
    }

}
