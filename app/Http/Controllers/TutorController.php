<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Support\TutorSearch;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    /** Newest reviews shown on a profile (Step 5b). */
    private const REVIEWS_SHOWN = 10;

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

        $reviews = Review::visible()->where('tutor_profile_id', $tutor['id'])
            ->with(['user:id,name', 'booking:id,subject_id', 'booking.subject:id,name'])
            ->latest()->limit(self::REVIEWS_SHOWN)->get()
            ->map->toCardArray()->all();

        return view('tutors.show', compact('tutor', 'similar', 'reviews'));
    }
}
