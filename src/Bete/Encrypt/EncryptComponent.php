<?php

namespace Bete\Encrypt;

use Bete\Support\Str;
use Bete\Foundation\Component;

class EncryptComponent extends Component
{
    public function register()
    {
        $this->app->singleton('encrypt', function($app) {
            $config = $app->config['app'];

            if (Str::startsWith($key = $config['key'], 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return new Encrypter($key);
        });
    }

    public function names()
    {
        return ['encrypt'];
    }
}
