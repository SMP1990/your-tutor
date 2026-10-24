<?php

namespace App\Http\Controllers;

use App\Enums\ClassStatus;
use App\Models\ClassBooking;
use App\Models\Review;
use App\Support\DashboardNav;
use Illuminate\Http\Request;

/**
 * Step 5a: after at least one regular class was held, the family rates the tutor (1-5 stars + a short
 * comment). One review per tutor per learner; saving again edits it. It shows at once (owner decision);
 * admins can hide it later (Step 5c).
 */
class ReviewController extends Controller
{
    public function edit(Request $request, ClassBooking $booking)
    {
        $this->ensureCanReview($request, $booking);

        return view('dashboard.review', [
            'booking' => $booking->load(['tutorProfile.user:id,name', 'subject:id,name']),
            'review' => $this->existing($booking),
            'nav' => DashboardNav::for($request->user()->role->value),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function update(Request $request, ClassBooking $booking)
    {
        $this->ensureCanReview($request, $booking);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:'.Review::MIN_COMMENT, 'max:'.Review::MAX_COMMENT],
        ], [
            'rating.required' => 'Choose 1 to 5 stars.',
            'comment.min' => 'Please write at least '.Review::MIN_COMMENT.' characters.',
        ]);

        $review = $this->existing($booking) ?? new Review;
        $isNew = ! $review->exists;
        $review->forceFill([
            'tutor_profile_id' => $booking->tutor_profile_id,
            'user_id' => $booking->user_id,
            'child_id' => $booking->child_id,
            'class_booking_id' => $booking->id,
            'rating' => (int) $data['rating'],
            'comment' => trim($data['comment']),
        ])->save();

        $booking->tutorProfile->refreshRating();

        return redirect()->route('dashboard')->with('notice', $isNew ? 'Thank you! Your review is now on the tutor\'s profile.' : 'Your review is updated.');
    }

    /** The family's review of this tutor for this learner, if any (one per tutor per learner). */
    private function existing(ClassBooking $booking): ?Review
    {
        return Review::where('tutor_profile_id', $booking->tutor_profile_id)
            ->where('user_id', $booking->user_id)
            ->where('child_id', $booking->child_id)
            ->first();
    }

    /** Only the family that booked, and only after at least one class was held (owner decision). */
    private function ensureCanReview(Request $request, ClassBooking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
        abort_unless($booking->sessions()->where('status', ClassStatus::Held)->exists(), 409,
            'You can review a tutor after your first class with them has taken place.');
    }
}
