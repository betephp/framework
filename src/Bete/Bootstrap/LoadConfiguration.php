<?php

namespace Bete\Bootstrap;

use Bete\Foundation\Application;
use Bete\Config\Repository;
use Bete\Support\Str;

class LoadConfiguration
{

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        $item = [];

        $this->app->instance('config', $config = new Repository());
        
        $this->loadConfigurationFiles($config);

        date_default_timezone_set($config['app.timezone']);

        mb_internal_encoding('UTF-8');
    }

    public function loadConfigurationFiles(Repository $repository)
    {
        foreach ($this->getConfigurationFiles() as $key => $path) {
            $repository->set($key, require($path));
        }
    }

    public function getConfigurationFiles()
    {
        $files = [];

        $configPath = realpath($this->app->configPath());

        $list = scandir($configPath);
        foreach ($list as $key => $filename) {
            if (Str::endsWith($filename, '.php')) {
                $name = substr($filename, 0, strrpos($filename, '.'));
                $files[$name] = $configPath . DIRECTORY_SEPARATOR . $filename;
            }
        }

        return $files;
    }

}
