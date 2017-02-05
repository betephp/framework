<?php

namespace Bete\Console;

use Bete\Foundation\Application;
use Bete\Exception\ConsoleException;

class Controller
{
    protected $app;

    public $id;

    public $defaultAction = 'index';

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

        $result = $this->runAction($actionId);

        return $result;
    }

    public function runAction($actionId)
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $actionId)) {

            $methodName = 'action' . ucfirst($actionId);

            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return call_user_func_array(
                        [$this, $methodName], [$this->app->request]);
                }
            }
        }

        throw new ConsoleException("Can not find {$actionId} action.");
    }

    public function json($data, $code = 0, $message = 'OK')
    {
        return $this->app->response->json($data, $code, $message);
    }
}
