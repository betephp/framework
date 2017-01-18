<?php

namespace Bete\Console\Action\Make;

use Bete\Console\Action;
use Bete\Console\Request;
use Bete\Exception\ConsoleMakeException;

class Web extends Action
{
    public function run(Request $request)
    {
        $name = $request->param(1);

        $pat = '/[a-zA-Z_][a-zA-Z0-9_]\/[a-zA-Z_][a-zA-Z0-9_]*/';
        if (!preg_match($pat, $name)) {
            throw new ConsoleMakeException(
                "The name parameter should be controller/action format.");
        }

        list($controller, $action) = explode('/', $name);

        $controller = ucfirst($controller);
        $action = ucfirst($action);

        $path = app()->make('path.app') . '/Web/' . $controller;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $path .= "/{$action}.php";

        if (file_exists($path)) {
            throw new ConsoleMakeException(
                "Web Action '{$controller}/{$action}' alreay exists");
        }

        $data = $this->buildContent($controller, $action);

        file_put_contents($path, $data);

        echo "Web Action is created successfully.\n";
    }

    public function buildContent($controller, $action)
    {
        $content = file_get_contents(__DIR__ . '/template/web.php');

        $search = ['{{controller}}', '{{action}}'];
        $replace = [$controller, $action];
        return str_replace($search, $replace, $content);
    }

}
