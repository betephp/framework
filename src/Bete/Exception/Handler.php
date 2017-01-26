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

    public function report(\Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        Log::error($e);
    }

    public function shouldntReport(\Exception $e)
    {
        $dontReport = array_merge(
            $this->dontReport, ['Bete\Exception\WebException']);

        foreach ($dontReport as $exception) {
            if ($e instanceof $exception) {
                return true;
            }
        }

        return false;
    }

    public function renderForConsole(\Exception $e)
    {
        return $this->renderConsoleException($e);
    }

    public function renderConsoleException(\Exception $e)
    {
        return "Error: " . $e->getMessage() . "\n";
    }


    public function renderForWeb(\Exception $e)
    {
        if ($e instanceof ValidationException) {
            return $this->renderValidationException($e);
        } elseif ($e instanceof WebNotFoundException) {
            $statusCode = $e->statusCode;
            header("HTTP/1.0 {$statusCode} NOT FOUND");
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

    public function renderJsonException(\Exception $e)
    {
        if ($e instanceof ValidationException) {
            $data = $e->validator;
        } else {
            $data = '';
        }

        return $this->app->response->json($data, $e->getCode(), $e->getMessage());
    }

    public function renderHtmlException(\Exception $e)
    {
        $data = [
            'message' => $e->getMessage(),
            'trace' => '',
        ];

        if ($this->app->env('dev', 'test')) {
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
