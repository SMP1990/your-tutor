<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Review;
use App\Models\User;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Step 6a: admins find users, read their details, suspend or re-open an account (never delete), and
 * mark an email as verified by hand. Every action is recorded with the admin and a reason.
 */
class UserController extends Controller
{
    public const STATUSES = [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'unverified' => 'Email not verified',
    ];

    public function index(Request $request)
    {
        $role = Role::tryFrom((string) $request->query('role'));
        $status = array_key_exists($request->query('status'), self::STATUSES) ? $request->query('status') : null;
        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->when($q !== '', function (Builder $query) use ($q) {
                $like = '%'.addcslashes(mb_substr($q, 0, 80), '%_\\').'%';
                $query->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->when($role, fn (Builder $query) => $query->where('role', $role))
            ->when($status, fn (Builder $query) => match ($status) {
                'active' => $query->whereNull('suspended_at'),
                'suspended' => $query->whereNotNull('suspended_at'),
                'unverified' => $query->whereNull('email_verified_at'),
            })
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'role' => $role?->value,
            'status' => $status,
            'roles' => Role::cases(),
            'statuses' => self::STATUSES,
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function show(Request $request, User $account)
    {
        $account->load(['children', 'tutorProfile', 'adminActions.admin:id,name']);

        return view('admin.users.show', [
            'account' => $account,
            'counts' => [
                'Free demo requests' => $account->demoRequests()->count(),
                'Regular class bookings' => $account->classBookings()->count(),
                'Reviews written' => Review::where('user_id', $account->id)->count(),
            ],
            'canSuspend' => $this->canChangeStatus($request->user(), $account),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function suspend(Request $request, User $account)
    {
        abort_unless($this->canChangeStatus($request->user(), $account), 403);
        abort_if($account->isSuspended(), 409);

        $reason = $this->reason($request);

        DB::transaction(function () use ($request, $account, $reason) {
            $account->forceFill(['suspended_at' => now(), 'suspended_reason' => $reason])->save();
            $this->record($request, $account, AdminAction::SUSPENDED, $reason);
        });

        // Cut off any browser that is logged in right now (the middleware also checks on every request).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $account->id)->delete();
        }

        return back()->with('notice', 'Account suspended. The user is logged out and cannot log in.');
    }

    public function unsuspend(Request $request, User $account)
    {
        abort_unless($this->canChangeStatus($request->user(), $account), 403);
        abort_unless($account->isSuspended(), 409);

        $reason = $this->reason($request);

        DB::transaction(function () use ($request, $account, $reason) {
            $account->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
            $this->record($request, $account, AdminAction::UNSUSPENDED, $reason);
        });

        return back()->with('notice', 'Account is active again.');
    }

    public function verifyEmail(Request $request, User $account)
    {
        abort_if($account->hasVerifiedEmail(), 409);

        $reason = $this->reason($request);

        DB::transaction(function () use ($request, $account, $reason) {
            $account->markEmailAsVerified();
            $this->record($request, $account, AdminAction::EMAIL_VERIFIED, $reason);
        });

        return back()->with('notice', 'Email marked as verified. This was recorded under your name.');
    }

    /** Nobody can suspend themselves or any admin (which also protects the last admin from a lock-out). */
    private function canChangeStatus(User $admin, User $account): bool
    {
        return $admin->isNot($account) && ! $account->hasRole(Role::Admin);
    }

    private function reason(Request $request): string
    {
        return $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']])['reason'];
    }

    private function record(Request $request, User $account, string $action, string $reason): void
    {
        (new AdminAction)->forceFill([
            'admin_id' => $request->user()->id,
            'user_id' => $account->id,
            'action' => $action,
            'reason' => $reason,
        ])->save();
    }
}
