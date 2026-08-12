<?php

namespace Scry\DatabaseManager\Http\Controllers;

use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('database-manager::app');
    }
}
