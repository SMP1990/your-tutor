<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TutorProfile;
use App\Support\TutorSearch;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $filters = TutorSearch::filters($request);

        return view('tutors.index', [
            'filters' => $filters,
            'tutors' => TutorSearch::run($filters),
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(['slug', 'name']),
        ]);
    }

    public function show(array $tutor)
    {
        $similar = TutorProfile::approved()
            ->whereKeyNot($tutor['id'])
            ->whereHas('subjects', fn ($s) => $s->whereIn('slug', $tutor['subject_slugs']))
            ->orderByDesc('reviews_count')->limit(3)->get()
            ->map->toPublicArray()->all();

        return view('tutors.show', compact('tutor', 'similar'));
    }
}
