<?php

use Bete\Foundation\Application;

if (!function_exists('app')) {
    function app($make = null, $parmas = [])
    {
        if (is_null($make)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($make, $parmas);
    }
}

if (!function_exists('ini')) {
    function ini($name = null, $defaultValue = null)
    {
        $file = app('path.config') . DIRECTORY_SEPARATOR . 'app.ini';

        if (!file_exists($file)) {
            exit("The config/app.ini file doesn't exist.");
        }

        $config = parse_ini_file($file);

        if (!$config) {
            return $defaultValue;
        }

        if (is_null($name)) {
            return $config;
        }

        return isset($config[$name]) ? $config[$name] : $defaultValue;
    }
}

if (! function_exists('runtime_path')) {
    function runtime_path($path = '')
    {
        return app('path.runtime').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
