<?php

namespace Scry\Http\Controllers;

use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('scry::index');
    }
}
