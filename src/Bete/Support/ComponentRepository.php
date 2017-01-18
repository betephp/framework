<?php

namespace Bete\Support;

use Exception;

class ComponentRepository
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function load()
    {
        $manifest = $this->loadManifest();

        foreach ($manifest['eager'] as $component) {
            $this->app->register($this->createComponent($component));
        }

        $this->app->addLazyComponents($manifest['lazy']);
    }

    public function loadManifest()
    {
        $this->manifestPath = $this->app['path.compiled'] . '/components.php';
        $components = $this->app->config['app.components'];

        $manifest = null;
        if (file_exists($this->manifestPath)) {
            $manifest = require($this->manifestPath);

            if (!is_array($manifest)) {
                throw new Exception("Manifest's content is not an array.");
            }
        }

        if (is_null($manifest) || $manifest['components'] != $components) {
            $manifest = $this->compileManifest($components);
        }

        return $manifest;
    }

    protected function writeManifest($manifest)
    {
        $content = '<?php return ' . var_export($manifest, true) . ';';
        
        if (!@file_put_contents($this->manifestPath, $content)) {
            throw new Exception("Fail to write {$this->manifestPath}.");
        }
    }

    protected function compileManifest($components)
    {
        $manifest = ['components' => $components, 'eager' => [], 'lazy' => []];

        foreach ($components as $component) {
            $instance = $this->createComponent($component);

            if ($instance->isLazy()) {
                foreach ($instance->names() as $name) {
                    $manifest['lazy'][$name] = $component;
                }
            } else {
                $manifest['eager'][] = $component;
            }
        }

        $this->writeManifest($manifest);

        return $manifest;
    }

    public function createComponent($component)
    {
        return new $component($this->app);
    }
}
