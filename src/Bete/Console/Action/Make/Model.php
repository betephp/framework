<?php

namespace Bete\Console\Action\Make;

use Bete\Console\Action;
use Bete\Console\Request;
use Bete\Exception\ConsoleMakeException;

class Model extends Action
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

        $path = app()->make('path.app') . '/Model';
        if (!file_exists($path)) {
            @mkdir($path, 0755);
        }

        $path .= "/{$name}.php";

        if (file_exists($path)) {
            throw new ConsoleMakeException(
                "Model '{$name}' alreay exists.");
        }

        $data = $this->buildContent($name);

        file_put_contents($path, $data);

        return "Model is created successfully.\n";
    }

    public function buildContent($name)
    {
        $content = file_get_contents(__DIR__ . '/template/model.php');

        return str_replace('{{model}}', $name, $content);
    }

}
