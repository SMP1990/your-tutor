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
        return $this->view('student', $request);
    }

    public function parent(Request $request)
    {
        return $this->view('parent', $request, ['children' => $request->user()->children()->orderBy('name')->get()]);
    }

    public function tutor(Request $request)
    {
        return $this->view('tutor', $request, ['profile' => $request->user()->tutorProfile]);
    }

    public function admin(Request $request)
    {
        return $this->view('admin', $request, [
            'usersByRole' => User::toBase()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'tutorsByStatus' => TutorProfile::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'waiting' => TutorProfile::whereIn('status', [TutorStatus::Submitted, TutorStatus::InterviewScheduled])->count(),
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
