<?php

namespace App\Web\{{controller}};

use Bete\Web\Action;
use Bete\Web\Request;

class {{action}} extends Action
{

    public $renderType = self::TYPE_HTML;

    public function middlewares()
    {
        return [];
    }

    public function run(Request $request)
    {
        return "Hello, {{controller}}/{{action}}.";
    }

}
