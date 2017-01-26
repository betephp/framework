<?php

namespace Bete\View;

use Bete\Foundation\Application;
use Bete\Web\Controller;
use Bete\Exception\Exception as BaseException;

class View
{
    protected $app;

    public $title;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function encode($content)
    {
        return htmlspecialchars($content, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }

    public function json($data, $code = 0, $message = 'OK')
    {
        $result = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        return json_encode($result);
    }

    public function render($view, $data = [], Controller $controller = null)
    {
        $controller = $controller;

        $content = $this->renderPartial($view, $data, $controller);

        return $this->renderContent($content, $controller);
    }

    public function renderPartial($view, $data = [], 
        Controller $controller = null)
    {
        if ($controller instanceof Controller) {
            $this->title = $controller->title;
        }

        $data['errors'] = [];
        if (($errors = app()->session->getFlash('errors')) != null) {
            $data = array_merge($data, ['errors' => $errors]);
        }

        $file = $this->getViewFile($view, $controller);

        if (!file_exists($file)) {
            throw new BaseException("The view file {$file} doesn't exists.");
        }

        return $this->renderFile($file, $data); 
    }

    protected function getViewFile($view, Controller $controller = null)
    {
        $viewPath = $this->app->make('path.view');

        if ($controller instanceof Controller) {
            $file = $viewPath . '/' . $controller->id . '/' . $view;
        } else {
            $file = $viewPath . "/{$view}";
        }

        return $file . '.php';
    }

    protected function renderContent($content, Controller $controller = null)
    {
        $layoutFile = $this->getLayoutFile($controller);
        if (!empty($layoutFile)) {
            $data = ['content' => $content];
            return $this->renderFile($layoutFile, $data);
        } else {
            return $content;
        }
    }
    protected function getLayoutFile(Controller $controller = null)
    {
        if ($controller !== null && !empty($controller->layout)) {
            $layout = $controller->layout;

            return $this->app->make('path.view') . "/layout/{$layout}.php";
        }

        return false;
    }

    protected function renderFile($__file, $__data = [])
    {
        ob_start();
        ob_implicit_flush(false);
        extract($__data, EXTR_OVERWRITE);
        require($__file);

        return ob_get_clean();
    }
}
