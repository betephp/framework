<?php

namespace App\Console;

use Bete\Console\Controller;
use Bete\Console\Request;

class {{controller}} extends Controller
{
    public function actionIndex(Request $request)
    {
        return "This is index action.\n";
    }
}
