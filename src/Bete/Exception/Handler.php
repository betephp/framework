<?php

namespace Bete\Exception;

use Bete\Foundation\Application;
use Bete\Log\Log;
use Bete\Exception\ValidationException;
use Bete\Exception\ConsoleMakeException;

class Handler
{
    protected $app;

    protected $dontReport = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function report($e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        Log::error($e);
    }

    public function shouldntReport($e)
    {
        $dontReport = array_merge($this->dontReport);

        foreach ($dontReport as $exception) {
            if ($e instanceof $exception) {
                return true;
            }
        }

        return false;
    }

    public function renderForConsole($e)
    {
        return $this->renderConsoleException($e);
    }

    public function renderConsoleException($e)
    {
        return "Error: " . $e->getMessage() . "\n";
    }


    public function renderForWeb($e)
    {
        if ($e instanceof ValidationException) {
            return $this->renderValidationException($e);
        } elseif ($e instanceof WebNotFoundException) {
            $statusCode = $e->statusCode;
            header("HTTP/1.0 {$statusCode} NOT FOUND");
            return $this->renderHtmlException($e);
        }

        if ($this->app->request->acceptsJson()) {
            return $this->renderJsonException($e);
        } else {
            return $this->renderHtmlException($e);
        }
    }

    public function renderValidationException($e)
    {
        if ($this->app->request->acceptsJson()) {
            return $this->renderJsonException($e);
        } else {
            $this->app->session->setFlash('errors', $e->validator);
            $this->app->response->redirect($this->app->request->getReferrer());
        }
    }

    public function renderJsonException($e)
    {
        $message = 'Whoops, something went wrong.';

        if ($e instanceof ValidationException) {
            $data = $e->validator;
        } else {
            $data = '';
        }

        return $this->app->view->json($data, $e->getCode(), $message);
    }

    public function renderHtmlException($e)
    {
        if ($e instanceof WebNotFoundException) {
            $title = 'Not Found';
        } else {
            $title = 'Whoops, something went wrong.';
        }

        $data = [
            'title' => $title,
            'message' => null,
            'trace' => null,
        ];

        if ($this->app->env('dev', 'test')) {
            $data['message'] = $e->getMessage();
            $data['trace'] = nl2br($this->getExceptionMessage($e));
        }

        return $this->app->view->render('error/index', $data);
    }

    public function getExceptionMessage($exception)
    {
        if ($this->app->env('dev', 'test')) {
            $message = "Exception '" . get_class($exception) 
                . "' with message '{$exception->getMessage()}'\nin "
                . $exception->getFile() . ':' . $exception->getLine()
                . "\nStack trace:\n" . $exception->getTraceAsString();
        } else {
            $message = 'Error ' . $exception->getMessage();
        }

        return $message;
    }

}
