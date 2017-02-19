<?php

namespace Bete\Http;

use Bete\Foundation\Application;
use Bete\Exception\Exception;

class Route
{
    protected $app;

    protected $request;

    protected $defaultRoute;

    protected $rules;

    protected $placeholders;

    protected $ruleParams = [];

    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;

        $this->prepare($this->app['config']['route']);
    }

    protected function prepare($config)
    {
        $this->defaultRoute = $config['default'];

        $this->prepareRules($config['rules']);
    }

    protected function prepareRules(array $rules)
    {
        $theRules = [];
        foreach ($rules as $pattern => $route) {
            $pattern = '/' . $pattern . '/';
            $tr = [];
            if (preg_match_all('/<([\w._-]+):?([^>]+)?>/', $pattern, $matches, 
                PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $name = $match[1];
                    $pat = isset($match[2]) ? $match[2] : '[^\/]+';
                    $placeholder = 'a' . hash('crc32b', $name);
                    $this->placeholders[$placeholder] = $name;

                    $tr["<$name>"] = "(?P<$placeholder>$pat)";

                    $this->ruleParams[$name] = '';
                }
            }
            $template = preg_replace('/<([\w._-]+):?([^>]+)?>/', 
                '<$1>', $pattern);
            $pattern = '#^' . trim(strtr($template, $tr), '/') . '$#u';

            $theRules[] = [
                'pattern' => $pattern,
                'route' => $route,
            ];
        }

        $this->rules = $theRules;
    }

    public function resolve()
    {
        $pathInfo = $this->request->pathInfo();
        $params = [];

        foreach ($this->rules as $rule) {
            if (preg_match($rule['pattern'], $pathInfo, $matches)) {
                $matches = $this->replacePlaceholders($matches);

                foreach ($matches as $name => $value) {
                    if (isset($this->ruleParams[$name])) {
                        $params[$name] = $value;
                    }
                }

                return [$rule['route'], $params];
            }
        }

        if ($pathInfo === '') {
            $pathInfo = $this->defaultRoute;
        }

        return [$pathInfo, $params];
    }

    protected function replacePlaceholders(array $matches)
    {
        foreach ($this->placeholders as $placeholder => $name) {
            if (isset($matches[$placeholder])) {
                $matches[$name] = $matches[$placeholder];
                unset($matches[$placeholder]);
            }
        }
        return $matches;
    }
}
