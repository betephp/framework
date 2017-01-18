<?php

namespace Bete\Validation;

use Bete\Foundation\Component;

class ValidationComponent extends Component
{
    public $lazy = true;

    public function register()
    {
        $this->app->singleton('validator', function ($app) {
            return new ValidatorManager($app);
        });
    }

    public function names()
    {
        return ['validator'];
    }
}
