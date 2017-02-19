<?php

namespace Bete\Database;

use Bete\Foundation\Component;
use Bete\Database\Model;
use Bete\Database\ConnectionFactory;
use Bete\Database\DatabaseManager;

class DatabaseComponent extends Component
{
    public function boot() 
    {
        Model::setConnectionResolver($this->app['db']);
    }

    public function register()
    {
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });
        
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });
    }

    public function names()
    {
        return ['db'];
    }
}
