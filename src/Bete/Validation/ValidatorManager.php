<?php

namespace Bete\Validation;

use Bete\Foundation\Application;

class ValidatorManager
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function make(array $data, array $rules, array $messages = [])
    {
        $params = [$data, $rules, $messages];
        return $this->app->make('Bete\Validation\Validator', $params);
    }

    public function validate(array $data, array $rules, array $messages = [])
    {
        return $this->make($data, $rules, $messages)->validate();
    }
}
 
