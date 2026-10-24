<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\DemoStatus;
use App\Http\Requests\BookDemoRequest;
use App\Models\ClassBooking;
use App\Models\DemoRequest;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Step 4a: after a completed free demo, the family asks the same tutor for regular weekly classes. */
class ClassBookingController extends Controller
{
    /** Start times offered, every 30 minutes from 7 am to 10 pm (site timezone). */
    public static function times(): array
    {
        $times = [];
        for ($m = 7 * 60; $m <= 22 * 60; $m += 30) {
            $value = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $times[$value] = date('g:i A', strtotime($value));
        }

        return $times;
    }

    public function create(Request $request, DemoRequest $demo)
    {
        $this->ensureBookable($request, $demo);
        $profile = $demo->tutorProfile;

        return view('dashboard.book-classes', [
            'demo' => $demo,
            'tutor' => $profile->toPublicArray(),
            'days' => array_keys(array_filter($profile->availability ?? [])),
            'times' => self::times(),
            'nav' => DashboardNav::for($request->user()->role->value),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function store(Request $request, DemoRequest $demo)
    {
        $this->ensureBookable($request, $demo);
        $profile = $demo->tutorProfile;
        $tz = config('site.timezone');

        $data = $request->validate([
            'days' => ['required', 'array', 'min:1', 'max:'.ClassBooking::MAX_DAYS],
            'days.*' => ['distinct', Rule::in(array_keys(array_filter($profile->availability ?? [])))],
            'start_time' => ['required', Rule::in(array_keys(self::times()))],
            'duration' => ['required', 'integer', Rule::in(array_keys(ClassBooking::DURATIONS))],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after:'.now($tz)->toDateString(), 'before_or_equal:'.now($tz)->addDays(30)->toDateString()],
            'weeks' => ['required', 'integer', Rule::in(array_keys(ClassBooking::WEEKS))],
            'grade' => ['required', Rule::in(BookDemoRequest::GRADES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'days.required' => 'Choose at least one day.',
            'days.*.in' => 'Choose only days the tutor is free.',
            'starts_on.after' => 'Choose a start date from tomorrow onwards.',
            'starts_on.before_or_equal' => 'Choose a start date within the next 30 days.',
        ]);

        $booking = new ClassBooking;
        $booking->forceFill([
            'tutor_profile_id' => $profile->id,
            'user_id' => $request->user()->id,
            'child_id' => $demo->child_id,
            'demo_request_id' => $demo->id,
            'subject_id' => $demo->subject_id,
            'student_name' => $demo->student_name,
            'grade' => $data['grade'],
            // Keep the week order (Mon..Sun) whatever order the boxes were ticked in.
            'days' => array_values(array_intersect(config('tutors.days'), $data['days'])),
            'start_time' => $data['start_time'],
            'duration_minutes' => (int) $data['duration'],
            'starts_on' => $data['starts_on'],
            'weeks' => (int) $data['weeks'],
            'rate_minor' => $profile->hourly_rate_minor,
            'currency' => $profile->currency,
            'notes' => $data['notes'] ?? null,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addHours(ClassBooking::ANSWER_HOURS),
        ])->save();

        return redirect()->route('dashboard')->with('notice', 'Request sent. '.$profile->user->name.' will reply within '.ClassBooking::ANSWER_HOURS.' hours.');
    }

    /** The family withdraws a booking the tutor has not answered yet. */
    public function cancel(Request $request, ClassBooking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
        abort_unless($booking->currentStatus() === BookingStatus::Pending, 409, 'This booking can no longer be cancelled here.');

        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        return back()->with('notice', 'Booking request cancelled.');
    }

    /** Only the family that booked, only after the demo took place, tutor still listed, no open booking yet. */
    private function ensureBookable(Request $request, DemoRequest $demo): void
    {
        abort_unless($demo->user_id === $request->user()->id, 404);
        abort_unless($demo->status === DemoStatus::Completed, 409, 'Regular classes can be booked after the free demo has taken place.');
        abort_unless($demo->tutorProfile?->isLive(), 409, 'This tutor is not taking bookings right now.');

        $alreadyBooked = ClassBooking::active()
            ->where('user_id', $demo->user_id)->where('tutor_profile_id', $demo->tutor_profile_id)
            ->where('child_id', $demo->child_id)->exists();
        abort_if($alreadyBooked, 409, 'You already have a booking with this tutor. Check your dashboard.');
    }
}
