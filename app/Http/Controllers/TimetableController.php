<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\ClassSession;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
            'isTutor' => $user->role === Role::Tutor,
            'nav' => DashboardNav::for($dashboard),
            'user' => ['name' => $user->name, 'role' => $user->role->label()],
        ]);
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
