<?php

namespace Bete\View;

use Bete\Foundation\Application;
use Bete\Web\Action;

class View
{
    protected $app;

    protected $action;

    public $title;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function encode($content)
    {
        return htmlspecialchars($content, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }

    public function render($view, $data = [], Action $action = null)
    {
        $this->action = $action;

        $content = $this->renderPartial($view, $data, $action);

        return $this->renderContent($content);
    }

    public function renderPartial($view, $data = [])
    {
        if ($this->action instanceof Action) {
            $this->title = $this->action->title;
        }

        $data['errors'] = [];
        if (($errors = app()->session->getFlash('errors')) != null) {
            $data = array_merge($data, ['errors' => $errors]);
        }

        $file = $this->getViewFile($view);

        return $this->renderFile($file, $data); 
    }

    protected function getViewFile($view)
    {
        return $this->app->make('path.view') . '/' . $view . '.php';
    }

    protected function renderContent($content)
    {
        $layoutFile = $this->getLayoutFile();
        if ($layoutFile !== false) {
            $data = ['content' => $content];
            return $this->renderFile($layoutFile, $data);
        } else {
            return $content;
        }
    }
    protected function getLayoutFile()
    {
        if ($this->action !== null && !empty($this->action->layout)) {
            $layout = $this->action->layout;

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
