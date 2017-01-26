<?php

namespace App\Web;

use App\Web\Controller;
use Bete\Web\Request;

class {{controller}} extends Controller
{
    public function actionIndex(Request $request)
    {
        return "This is index action.";
    }
}
