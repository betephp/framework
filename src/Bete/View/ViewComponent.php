<?php

namespace Bete\View;

use Bete\Foundation\Component;

class ViewComponent extends Component
{

    public function register()
    {
        $this->app->bind('view', function ($app) {
            return new View($app);
        });
    }

    public function names()
    {
        return ['view'];
    }

}
