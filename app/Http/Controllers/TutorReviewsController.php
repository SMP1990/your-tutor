<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\DashboardNav;
use Illuminate\Http\Request;

/** Step 5b: a tutor reads the reviews families gave them (the same ones shown on the public profile). */
class TutorReviewsController extends Controller
{
    public function __invoke(Request $request)
    {
        $profile = $request->user()->tutorProfile()->first();

        return view('dashboard.tutor-reviews', [
            'profile' => $profile,
            'reviews' => $profile
                ? Review::visible()->where('tutor_profile_id', $profile->id)
                    ->with(['user:id,name', 'booking:id,subject_id', 'booking.subject:id,name'])
                    ->latest()->paginate(20)
                : collect(),
            'nav' => DashboardNav::for('tutor'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }
}
