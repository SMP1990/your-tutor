<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Step 4d: admins see every regular-class booking and every class. Read-only for now. */
class ClassesController extends Controller
{
    public const BOOKING_TABS = [
        'waiting' => 'Waiting for tutor',
        'unanswered' => 'Not answered in time',
        'confirmed' => 'Confirmed',
        'closed' => 'Declined / cancelled',
    ];

    public const CLASS_TABS = [
        'upcoming' => 'Upcoming',
        'unmarked' => 'Attendance not marked',
        'held' => 'Held',
        'absent' => 'Student absent',
        'cancelled' => 'Cancelled',
    ];

    public function bookings(Request $request)
    {
        $tab = $this->tab($request, self::BOOKING_TABS, 'waiting');

        return view('admin.classes.bookings', $this->frame($request) + [
            'tab' => $tab,
            'tabs' => collect(self::BOOKING_TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::bookingTab($key)->count()]),
            'bookings' => self::bookingTab($tab)
                ->with(['user:id,name,email', 'tutorProfile:id,user_id', 'tutorProfile.user:id,name', 'subject:id,name'])
                ->withCount(['sessions', 'sessions as held_count' => fn ($q) => $q->where('status', ClassStatus::Held)])
                ->latest()->paginate(25)->withQueryString(),
        ]);
    }

    public function classes(Request $request)
    {
        $tab = $this->tab($request, self::CLASS_TABS, 'upcoming');

        return view('admin.classes.classes', $this->frame($request) + [
            'tab' => $tab,
            'tabs' => collect(self::CLASS_TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::classTab($key)->count()]),
            'sessions' => self::classTab($tab)
                ->with(['booking:id,tutor_profile_id,user_id,subject_id,student_name', 'booking.user:id,name',
                    'booking.tutorProfile:id,user_id', 'booking.tutorProfile.user:id,name', 'booking.subject:id,name'])
                ->when($tab === 'upcoming', fn ($q) => $q->orderBy('starts_at'), fn ($q) => $q->orderByDesc('starts_at'))
                ->paginate(30)->withQueryString(),
        ]);
    }

    public static function bookingTab(string $tab): Builder
    {
        $q = ClassBooking::query();

        return match ($tab) {
            'waiting' => $q->where('status', BookingStatus::Pending)->where('expires_at', '>', now()),
            'unanswered' => $q->where('status', BookingStatus::Pending)->where('expires_at', '<=', now()),
            'confirmed' => $q->where('status', BookingStatus::Accepted),
            'closed' => $q->whereIn('status', [BookingStatus::Declined, BookingStatus::Cancelled]),
        };
    }

    public static function classTab(string $tab): Builder
    {
        $q = ClassSession::query();

        return match ($tab) {
            'upcoming' => $q->where('status', ClassStatus::Scheduled)->where('starts_at', '>', now()),
            'unmarked' => $q->where('status', ClassStatus::Scheduled)->where('starts_at', '<=', now()),
            'held' => $q->where('status', ClassStatus::Held),
            'absent' => $q->where('status', ClassStatus::StudentAbsent),
            'cancelled' => $q->whereIn('status', [ClassStatus::TutorCancelled, ClassStatus::FamilyCancelled]),
        };
    }

    private function tab(Request $request, array $tabs, string $default): string
    {
        return array_key_exists($request->query('tab'), $tabs) ? $request->query('tab') : $default;
    }

    private function frame(Request $request): array
    {
        return [
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ];
    }
}
