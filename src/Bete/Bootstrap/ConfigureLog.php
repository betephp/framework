<?php

namespace Bete\Bootstrap;

use Bete\Foundation\Application;
use Bete\Log\Logger;

class ConfigureLog
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        $this->app->instance('log', $log = new Logger($this->app));

        $logDateSuffix = $this->app->config['app.log.date_suffix'];
        $logTraceLevel = $this->app->config['app.log.trace_level'];

        $this->app->log->setDateSuffix($logDateSuffix);
        $this->app->log->setTraceLevel($logTraceLevel);
    }
}
