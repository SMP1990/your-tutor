<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TutorProfile;

class SubjectController extends Controller
{
    public function index()
    {
        return view('subjects.index', ['subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function show(string $slug)
    {
        $subject = Subject::where('is_active', true)->where('slug', $slug)->first() ?? abort(404);

        return view('subjects.show', [
            'subject' => $subject,
            'tutors' => $subject->tutors()->approved()->with(['user:id,name', 'subjects:id,name,slug'])
                ->orderByDesc('reviews_count')->get()
                ->map(fn (TutorProfile $p) => $p->toPublicArray())->all(),
            'others' => Subject::where('is_active', true)->whereKeyNot($subject->id)->orderBy('sort_order')->get(),
        ]);
    }
}
