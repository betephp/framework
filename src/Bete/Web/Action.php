<?php

namespace Bete\Web;

use Bete\Foundation\Application;
use Bete\Exception\ValidationException;
use Bete\Web\Request;
use Bete\View\View;

abstract class Action
{
    protected $app;

    const TYPE_RAW = 'raw';
    const TYPE_JSON = 'json';
    const TYPE_HTML = 'html';

    public $renderType = self::TYPE_HTML;

    public $layout = null;

    public $title = 'Page Title';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract function run(Request $request);

    public function middlewares()
    {
        return [];
    }

    public function render($view, $data = [])
    {
        return $this->app->view->render($view, $data, $this);
    }


    public function json($data, $code = 0, $message = 'OK')
    {
        return $this->app->response->json($data, $code, $message);
    }
}
