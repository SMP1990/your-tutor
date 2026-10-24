<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Enums\Role;
use App\Models\ClassSession;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Step 4b: upcoming regular classes for the family and for the tutor, with "Join class". */
class TimetableController extends Controller
{
    /** Classes of confirmed bookings the user may see: the family's own, or the tutor's. */
    public static function sessionsFor($user): Builder
    {
        return ClassSession::query()
            ->whereHas('booking', function (Builder $b) use ($user) {
                $b->where('status', BookingStatus::Accepted);
                $user->role === Role::Tutor
                    ? $b->where('tutor_profile_id', $user->tutorProfile()->value('id'))
                    : $b->where('user_id', $user->id);
            })
            ->with(['booking:id,tutor_profile_id,user_id,subject_id,student_name,meet_link,duration_minutes',
                'booking.tutorProfile:id,user_id', 'booking.tutorProfile.user:id,name', 'booking.subject:id,name']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $dashboard = $user->role->value;

        return view('dashboard.timetable', [
            'sessions' => self::sessionsFor($user)->upcoming()->paginate(20),
            'toMark' => $user->role === Role::Tutor ? self::sessionsFor($user)->toMark()->limit(30)->get() : collect(),
            'past' => self::sessionsFor($user)->past()->limit(30)->get(),
            'isTutor' => $user->role === Role::Tutor,
            'nav' => DashboardNav::for($dashboard),
            'user' => ['name' => $user->name, 'role' => $user->role->label()],
        ]);
    }

    /** Step 4c: the tutor marks a class held / student absent (after it started) or cancels it. */
    public function mark(Request $request, ClassSession $session)
    {
        $this->ensureOwn($request, $session);

        $data = $request->validateWithBag("class{$session->id}", [
            'status' => ['required', Rule::in([ClassStatus::Held->value, ClassStatus::StudentAbsent->value, ClassStatus::TutorCancelled->value])],
            'note' => ['nullable', 'required_if:status,'.ClassStatus::TutorCancelled->value, 'string', 'max:300'],
        ], ['note.required_if' => 'Write a short reason for cancelling.']);

        $status = ClassStatus::from($data['status']);
        abort_unless($session->tutorMayMark($status), 409, 'This class cannot be marked that way now.');
        $session->mark($status, $request->user(), $data['note'] ?? null);

        return back()->with('notice', $session->label().': '.$status->label().'.');
    }

    /** Step 4c: the family cancels a class at least FAMILY_CANCEL_HOURS before it starts. */
    public function cancel(Request $request, ClassSession $session)
    {
        $this->ensureOwn($request, $session);
        abort_unless($session->familyMayCancel(), 409,
            'Classes can be cancelled up to '.ClassSession::FAMILY_CANCEL_HOURS.' hours before they start. Please message your tutor.');

        $note = $request->validate(['note' => ['nullable', 'string', 'max:300']])['note'] ?? null;
        $session->mark(ClassStatus::FamilyCancelled, $request->user(), $note);

        return back()->with('notice', 'Class on '.$session->label().' cancelled. Your tutor will see it.');
    }

    private function ensureOwn(Request $request, ClassSession $session): void
    {
        abort_unless(self::sessionsFor($request->user())->whereKey($session->id)->exists(), 404);
    }

    /** Hands out the Google Meet link only to the class's family or tutor, and only around class time. */
    public function join(Request $request, ClassSession $session)
    {
        abort_unless(self::sessionsFor($request->user())->whereKey($session->id)->exists(), 404);

        if (! $session->canJoinNow()) {
            return redirect()->route('classes.index')->with('notice', $session->starts_at->isFuture()
                ? 'The class opens '.ClassSession::JOIN_OPENS_BEFORE.' minutes before it starts ('.$session->label().').'
                : 'This class has ended.');
        }

        return redirect()->away($session->booking->meet_link);
    }
}
