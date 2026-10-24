<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\TutorStatus;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\DashboardNav;
use Illuminate\Http\Request;

/** Real dashboards (logged-in, verified, own role only). Sections grow with each build step. */
class DashboardController extends Controller
{
    public function redirect(Request $request)
    {
        return redirect()->route(match ($request->user()->role) {
            Role::Student => 'dashboard.student',
            Role::Parent => 'dashboard.parent',
            Role::Tutor => 'dashboard.tutor',
            Role::Admin => 'dashboard.admin',
        });
    }

    public function student(Request $request)
    {
        return $this->view('student', $request, $this->classData($request));
    }

    public function parent(Request $request)
    {
        return $this->view('parent', $request, [
            'children' => $request->user()->children()->orderBy('name')->get(),
        ] + $this->classData($request));
    }

    /** Demo requests and regular-class bookings for a parent or student dashboard. */
    private function classData(Request $request): array
    {
        $bookings = $request->user()->classBookings()
            ->with(['tutorProfile:id,user_id', 'tutorProfile.user:id,name', 'subject:id,name'])
            ->withCount(['sessions', 'sessions as held_count' => fn ($q) => $q->where('status', \App\Enums\ClassStatus::Held)])
            ->latest()->limit(10)->get();

        return [
            'nextClasses' => TimetableController::sessionsFor($request->user())->upcoming()->limit(3)->get(),
            'demos' => $this->demos($request),
            'bookings' => $bookings,
            // "tutor-child" pairs that already have a waiting or confirmed booking: no second "Book" button.
            // "tutor-child" pairs this family already reviewed: the button says "Edit your review".
            'reviewedPairs' => \App\Models\Review::where('user_id', $request->user()->id)->get(['tutor_profile_id', 'child_id'])
                ->map(fn ($r) => $r->tutor_profile_id.'-'.$r->child_id)->all(),
            'bookedPairs' => $request->user()->classBookings()->active()->get(['tutor_profile_id', 'child_id'])
                ->map(fn ($b) => $b->tutor_profile_id.'-'.$b->child_id)->all(),
        ];
    }

    private function demos(Request $request)
    {
        return $request->user()->demoRequests()
            ->with(['tutorProfile:id,user_id,slug', 'tutorProfile.user:id,name', 'subject:id,name'])
            ->latest()->limit(10)->get();
    }

    public function tutor(Request $request)
    {
        return $this->view('tutor', $request, [
            'profile' => $request->user()->tutorProfile,
            'nextClasses' => TimetableController::sessionsFor($request->user())->upcoming()->limit(3)->get(),
        ]);
    }

    public function admin(Request $request)
    {
        return $this->view('admin', $request, [
            'usersByRole' => User::toBase()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'tutorsByStatus' => TutorProfile::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'waiting' => TutorProfile::whereIn('status', [TutorStatus::Submitted, TutorStatus::InterviewScheduled])->count(),
            'demoCounts' => collect(['waiting', 'unanswered', 'outcome'])
                ->mapWithKeys(fn ($tab) => [$tab => \App\Http\Controllers\Admin\DemoRequestController::tab($tab)->count()]),
            'classCounts' => [
                'bookings_waiting' => \App\Http\Controllers\Admin\ClassesController::bookingTab('waiting')->count(),
                'bookings_unanswered' => \App\Http\Controllers\Admin\ClassesController::bookingTab('unanswered')->count(),
                'classes_upcoming' => \App\Http\Controllers\Admin\ClassesController::classTab('upcoming')->count(),
                'classes_unmarked' => \App\Http\Controllers\Admin\ClassesController::classTab('unmarked')->count(),
            ],
            'reviewCounts' => collect(\App\Http\Controllers\Admin\ReviewModerationController::TABS)
                ->map(fn ($label, $tab) => \App\Http\Controllers\Admin\ReviewModerationController::tab($tab)->count()),
            'latestUsers' => User::latest()->limit(8)->get(['id', 'name', 'email', 'role', 'email_verified_at', 'created_at']),
        ]);
    }

    private function view(string $dashboard, Request $request, array $data = [])
    {
        $user = $request->user();

        return view("dashboard.{$dashboard}", $data + [
            'nav' => DashboardNav::for($dashboard),
            'user' => ['name' => $user->name, 'role' => $user->role->label()],
            'account' => $user,
        ]);
    }
}
