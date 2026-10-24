<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TutorProfile;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'tutors' => TutorProfile::approved()->with(['user:id,name', 'subjects:id,name,slug'])
                ->orderByDesc('rating_avg')->orderByDesc('reviews_count')->limit(3)->get()
                ->map->toPublicArray()->all(),
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
