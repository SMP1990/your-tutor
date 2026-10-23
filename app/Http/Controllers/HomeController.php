<?php

namespace App\Http\Controllers;

use App\Support\DemoSubjects;
use App\Support\DemoTutors;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'tutors' => DemoTutors::only(['ayesha-r', 'hamza-s', 'mehwish-t']),
            'subjects' => DemoSubjects::all(),
        ]);
    }
}
