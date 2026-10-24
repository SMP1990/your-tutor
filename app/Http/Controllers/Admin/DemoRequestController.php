<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DemoStatus;
use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Step 3d: admins see every free demo request, especially the ones nobody answered. Read-only for now. */
class DemoRequestController extends Controller
{
    /** Tab key => label. Queries are in tab(). */
    public const TABS = [
        'waiting' => 'Waiting for tutor',
        'unanswered' => 'Not answered in time',
        'upcoming' => 'Upcoming',
        'outcome' => 'Tutor to confirm',
        'done' => 'Done / no-show',
        'closed' => 'Declined / cancelled',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'waiting';

        return view('admin.demos.index', [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::tab($key)->count()]),
            'demos' => self::tab($tab)
                ->with(['user:id,name,email', 'tutorProfile:id,user_id,slug', 'tutorProfile.user:id,name', 'subject:id,name'])
                ->when(in_array($tab, ['upcoming', 'outcome'], true), fn ($q) => $q->orderBy('scheduled_at'), fn ($q) => $q->latest())
                ->paginate(25)->withQueryString(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public static function tab(string $tab): Builder
    {
        $q = DemoRequest::query();

        return match ($tab) {
            'waiting' => $q->open(),
            'unanswered' => $q->where('status', DemoStatus::Pending)->where('expires_at', '<=', now()),
            'upcoming' => $q->where('status', DemoStatus::Accepted)->where('scheduled_at', '>', now()),
            'outcome' => $q->where('status', DemoStatus::Accepted)->where('scheduled_at', '<=', now()),
            'done' => $q->whereIn('status', [DemoStatus::Completed, DemoStatus::NoShow]),
            'closed' => $q->whereIn('status', [DemoStatus::Declined, DemoStatus::Cancelled]),
        };
    }
}
