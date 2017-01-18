<?php

namespace Bete\Console\Action\Make;

use Bete\Console\Action;
use Bete\Console\Request;
use Bete\Exception\ConsoleMakeException;

class Middleware extends Action
{
    public function run(Request $request)
    {
        $name = $request->param(1);

        if (empty($name)) {
            throw new ConsoleMakeException("Missing name argument.");
        } elseif (!preg_match('/[a-zA-Z_][a-zA-Z0-9_]*/', $name)) {
            throw new ConsoleMakeException(
                "The name argument should follow PHP class's convention.");
        }

        $name = ucfirst($name);

        $middlewarePath = app()->make('path.app') . '/Middleware';
        if (!file_exists($middlewarePath)) {
            @mkdir($middlewarePath, 0755);
        }

        $middlewarePath .= "/{$name}.php";

        if (file_exists($middlewarePath)) {
            throw new ConsoleMakeException(
                "Middleware '{$name}' alreay exists.");
        }

        $data = $this->buildContent($name);

        file_put_contents($middlewarePath, $data);

        return "Middleware is created successfully.\n";
    }

    public function buildContent($name)
    {
        $content = file_get_contents(__DIR__ . '/template/middleware.php');

        return str_replace('{{middleware}}', $name, $content);
    }

}
