<?php

namespace Bete\Bootstrap;

use Exception;
use ErrorException;
use Bete\Foundation\Application;
use Bete\Exception\FatalErrorException;
use App\View\View;

class HandleException
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        error_reporting(-1);

        $this->app->singleton('view', function($app) {
            return new View($app);
        });

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);

        if ($this->app->env('prod')) {
            ini_set('display_errors', 'Off');
        }
    }

    public function handleError($level, $message, $file = '', 
        $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException($e)
    {
        $this->getExceptionHandler()->report($e);

        if ($this->app->runningInConsole()) {
            $this->renderForConsole($e);
        } else {
            $this->renderForWeb($e);
        }
    }

    public function renderForConsole($e)
    {
        echo $this->getExceptionHandler()->renderForConsole($e);
    }

    public function renderForWeb($e)
    {
        echo $this->getExceptionHandler()->renderForWeb($e);
    }

    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) 
            && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error));
        }
    }

    protected function isFatal($type)
    {
        return in_array($type, 
            [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    public function fatalExceptionFromError(array $error)
    {
        return new FatalErrorException($error['message'], 
            $error['type'], 0, $error['file'], $error['line']);
    }

    public function getExceptionHandler()
    {
        return $this->app->make('exceptionHandler');
    }
}
