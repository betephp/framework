<?php

namespace Bete\Foundation;

use Bete\Foundation\Container;

class Application extends Container
{

    public static $app;

    protected $basePath;

    protected $isBootstrapped = false;

    protected $lazyComponents = [];

    protected $components = [];

    protected $loadedComponents = [];

    protected $booted = false;

    public function __construct($basePath = null)
    {
        $this->setBasePath($basePath);

        $this->registerBaseBindings();

        $this->registerPaths();
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
    }

    public function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('Bete\Foundation\Application', $this);
    }

    public static function setInstance(Application $app)
    {
        static::$app = $app;
    }

    public static function getInstance()
    {
        return static::$app;
    }

    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    protected function registerPaths()
    {
        $this->instance('path', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.view', $this->viewPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.runtime', $this->runtimePath());
        $this->instance('path.compiled', $this->compiledPath());
        $this->instance('path.log', $this->logPath());
        $this->instance('path.session', $this->sessionPath());
    }

    public function basePath()
    {
        return $this->basePath;
    }

    public function appPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    public function viewPath()
    {
        return $this->appPath() . DIRECTORY_SEPARATOR . 'view';
    }

    public function configPath()
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . 'config';
    }

    public function runtimePath()
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . 'runtime';
    }

    public function compiledPath()
    {
        return $this->runtimePath() . DIRECTORY_SEPARATOR . 'compiled';
    }

    public function logPath()
    {
        return $this->runtimePath() . DIRECTORY_SEPARATOR . 'log';
    }

    public function sessionPath()
    {
        return $this->runtimePath() . DIRECTORY_SEPARATOR . 'session';
    }

    public function bootstrapWith(array $bootstrappers)
    {
        $this->isBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap();
        }
    }

    public function isBootstrapped()
    {
        return $this->isBootstrapped;
    }

    public function boot()
    {
        if ($this->booted) {
            return;
        }

        array_walk($this->components, function($p) {
            $this->bootComponent($p);
        });

        $this->booted = true;
    }
    
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->lazyComponents[$abstract])) {
            $this->loadLazyComponent($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    public function register($component)
    {
        if ($registered = $this->getComponent($component)) {
            return $registered;
        }

        if (is_string($component)) {
            $component = $this->createComponent();
        }

        if (method_exists($component, 'register')) {
            $component->register();
        }

        $this->markAsRegistered($component);

        if ($this->booted) {
            $this->bootComponent($component);
        }

        return $component;
    }

    public function bootComponent($component)
    {
        if (method_exists($component, 'boot')) {
            return $component->boot();
        }
    }

    public function getComponent($component)
    {
        $name = is_string($component) ? $component : get_class($component);

        foreach ($this->components as $component) {
            if ($component instanceof $name) {
                return $component;
            }
        }

        return false;
    }

    protected function markAsRegistered($component)
    {
        $this->components[] = $component;
    }

    public function createComponent($component)
    {
        return new $component($this);
    }

    public function loadLazyComponent($name)
    {
        if (!isset($this->lazyComponents[$name])) {
            return;
        }

        $component = $this->lazyComponents[$name];

        if (!isset($this->loadedComponents[$component])) {
            $this->registerLazyComponent($component, $name);
        }
    }

    public function registerLazyComponent($component, $name = null)
    {
        if ($name) {
            unset($this->lazyComponents[$name]);
        }

        $this->register($instance = new $component($this));
    }

    public function addLazyComponents(array $components)
    {
        $this->lazyComponents = array_merge($this->lazyComponents, $components);
    }

    public function env()
    {
        if (func_num_args() > 0) {
            $envs = func_get_args();
            foreach ($envs as $env) {
                if ($env == $this->config['app.env']) {
                    return true;
                }
            }
            return false;
        }

        return $this->config['app.env'];
    }

}
