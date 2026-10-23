<?php

namespace App\Http\Controllers;

use App\Support\DemoTutors;

class TutorController extends Controller
{
    public function index()
    {
        $tutors = DemoTutors::only(['ayesha-r', 'usman-f', 'sana-n', 'bilal-k', 'zainab-h', 'omar-a']);

        return view('tutors.index', compact('tutors'));
    }

    public function show(array $tutor)
    {
        return view('tutors.show', [
            'tutor' => $tutor,
            'similar' => DemoTutors::similarTo($tutor),
        ]);
    }
}
