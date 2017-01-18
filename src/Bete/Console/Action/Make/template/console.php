<?php

namespace App\Console\{{controller}};

use Bete\Console\Action;
use Bete\Console\Request;

class {{action}} extends Action
{
    public function run(Request $request)
    {
        return "Hello, {{controller}}:{{action}}.\n";
    }
}
