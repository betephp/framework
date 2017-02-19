<?php

namespace App\Http;

use App\Http\Controller;
use Bete\Http\Request;

class {{controller}} extends Controller
{
    public function actionIndex(Request $request)
    {
        return "This is index action.";
    }
}
