<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\ClassBooking;
use App\Notifications\BookingAnswered;
use App\Support\ClassSchedule;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Step 4a: a tutor accepts (with a Google Meet link) or declines a regular-classes booking. */
class TutorBookingController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->tutorProfile()->first();
        $bookings = $profile
            ? $profile->classBookings()->with('subject:id,name')->latest()->limit(50)->get()
            : collect();

        return view('dashboard.tutor-bookings', [
            'waiting' => $bookings->filter(fn ($b) => $b->currentStatus() === BookingStatus::Pending),
            'confirmed' => $bookings->filter(fn ($b) => $b->status === BookingStatus::Accepted),
            'other' => $bookings->reject(fn ($b) => in_array($b->currentStatus(), [BookingStatus::Pending, BookingStatus::Accepted], true)),
            'nav' => DashboardNav::for('tutor'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function accept(Request $request, ClassBooking $booking)
    {
        $this->ensureWaiting($request, $booking);

        $data = $request->validateWithBag("booking{$booking->id}", [
            'meet_link' => ['required', 'url:https', 'max:255', 'regex:#^https://meet\.google\.com/[a-z0-9-]+$#i'],
            'tutor_note' => ['nullable', 'string', 'max:500'],
        ], ['meet_link.regex' => 'Paste a Google Meet link, e.g. https://meet.google.com/abc-defg-hij']);

        DB::transaction(function () use ($booking, $data) {
            $booking->forceFill([
                'status' => BookingStatus::Accepted,
                'meet_link' => $data['meet_link'],
                'tutor_note' => $data['tutor_note'] ?? null,
                'answered_at' => now(),
            ])->save();
            ClassSchedule::create($booking); // every class date, for the timetable (Step 4b)
        });
        $booking->user->notify(new BookingAnswered($booking));

        return redirect()->route('tutor.bookings')->with('notice', "Confirmed. {$booking->student_name}'s family has been told.");
    }

    public function decline(Request $request, ClassBooking $booking)
    {
        $this->ensureWaiting($request, $booking);

        $data = $request->validateWithBag("booking{$booking->id}",
            ['tutor_note' => ['required', 'string', 'max:500']],
            ['tutor_note.required' => 'Write a short reason, e.g. "These times are taken; Tue and Thu work."'],
        );

        $booking->forceFill(['status' => BookingStatus::Declined, 'tutor_note' => $data['tutor_note'], 'answered_at' => now()])->save();
        $booking->user->notify(new BookingAnswered($booking));

        return redirect()->route('tutor.bookings')->with('notice', 'Declined. The family has been told.');
    }

    private function ensureWaiting(Request $request, ClassBooking $booking): void
    {
        abort_unless($booking->tutor_profile_id === $request->user()->tutorProfile()->value('id'), 404);
        abort_unless($booking->currentStatus() === BookingStatus::Pending, 409, 'This booking is no longer waiting for an answer.');
    }
}
