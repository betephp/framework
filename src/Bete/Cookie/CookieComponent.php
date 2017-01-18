<?php

namespace Bete\Cookie;

use Bete\Foundation\Component;

class CookieComponent extends Component
{
    public function register()
    {
        $this->app->singleton('cookie', function($app) {
            return new Cookie($app);
        });
    }

    public function names()
    {
        return ['cookie'];
    }
}
