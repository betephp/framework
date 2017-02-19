<?php

namespace Bete\Console;

use Exception;
use ReflectionMethod;
use Bete\Support\Str;
use Bete\Foundation\Application;
use Bete\Console\Route;
use Bete\Exception\ConsoleException;
use Bete\Exception\ConsoleMakeException as MakeException;
use Bete\Console\Request;

class MakeController extends Controller
{
    public function actionHttp(Request $request)
    {
        $name = $request->param(1);

        $pat = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
        if (!preg_match($pat, $name)) {
            throw new MakeException("The param name is illegel.");
        }

        $path = $this->app->make('path.app');

        $name = ucfirst($name) . 'Controller';

        $path .= "/Http/{$name}.php";

        if (file_exists($path)) {
            throw new MakeException("The {$name} alreay exists.");
        }

        $data = [
            '{{controller}}' => $name,
        ];
        $content = $this->buildContent('http', $data);

        file_put_contents($path, $content);

        echo "The http {$name} is created successfully.\n";
    }

    public function actionConsole(Request $request)
    {
        $name = $request->param(1);

        $pat = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
        if (!preg_match($pat, $name)) {
            throw new MakeException(
                "The console controller name is illegel.");
        }

        if (strtolower($name) === 'make') {
            throw new MakeException(
                "Can not create 'make' controller, it's used by framework.");
        }

        $path = $this->app->make('path.app');

        $name = ucfirst($name) . 'Controller';

        $path .= "/Console/{$name}.php";

        if (file_exists($path)) {
            throw new MakeException("The {$name} alreay exists.");
        }

        $data = [
            '{{controller}}' => $name,
        ];
        $content = $this->buildContent('console', $data);

        file_put_contents($path, $content);

        echo "The console {$name} is created successfully.\n";
    }

    public function actionMiddleware(Request $request)
    {
        $name = $request->param(1);

        $pat = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
        if (!preg_match($pat, $name)) {
            throw new MakeException("The middleware name is illegel.");
        }

        $path = $this->app->make('path.app');

        $path .= "/Middleware/{$name}.php";

        if (file_exists($path)) {
            throw new MakeException("The {$name} alreay exists.");
        }

        $data = [
            '{{middleware}}' => $name,
        ];
        $content = $this->buildContent('middleware', $data);

        file_put_contents($path, $content);

        echo "The middleware {$name} is created successfully.\n";
    }

    public function actionModel(Request $request)
    {
        $name = $request->param(1);

        $pat = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
        if (!preg_match($pat, $name)) {
            throw new MakeException("The model name is illegel.");
        }

        $path = $this->app->make('path.app');

        $path .= "/Model/{$name}.php";

        if (file_exists($path)) {
            throw new MakeException("The {$name} alreay exists.");
        }

        $table = Str::snake($name);

        $data = [
            '{{model}}' => $name,
            '{{table}}' => $table,
        ];
        $content = $this->buildContent('model', $data);

        file_put_contents($path, $content);

        echo "The model {$name} is created successfully.\n";
    }

    public function buildContent($template, $data)
    {
        $content = file_get_contents(__DIR__ . "/template/{$template}.php");

        $search = [];
        $replace = [];
        foreach ($data as $key => $value) {
            $search[] = $key;
            $replace[] = $value;
        } 

        return str_replace($search, $replace, $content);
    }
}
