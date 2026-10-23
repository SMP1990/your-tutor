<?php

namespace App\Http\Controllers;

use App\Support\DemoSubjects;

class SubjectController extends Controller
{
    public function index()
    {
        return view('subjects.index', ['subjects' => DemoSubjects::all()]);
    }

    public function show(string $slug)
    {
        $subject = DemoSubjects::find($slug) ?? abort(404);

        return view('subjects.show', [
            'subject' => $subject,
            'tutors' => DemoSubjects::tutorsFor($subject),
            'others' => array_values(array_filter(DemoSubjects::all(), fn ($s) => $s['slug'] !== $slug)),
        ]);
    }
}
