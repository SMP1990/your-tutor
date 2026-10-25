<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Step 6b: admins read reports, set the status, keep a private note and write a reply that the
 * person who reported sees on their dashboard. No email for now (owner decision).
 */
class ComplaintController extends Controller
{
    public const TABS = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'closed' => 'Closed',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'new';

        return view('admin.complaints.index', [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::tab($key)->count()]),
            'complaints' => self::tab($tab)->with(['user:id,name,role', 'booking.tutorProfile.user:id,name'])->latest('id')->paginate(25)->withQueryString(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function show(Request $request, Complaint $complaint)
    {
        return view('admin.complaints.show', [
            'complaint' => $complaint->load(['user:id,name,email,phone,role', 'booking.user:id,name,email,phone', 'booking.tutorProfile.user:id,name,email', 'booking.subject:id,name', 'session', 'repliedBy:id,name']),
            'statuses' => ComplaintStatus::cases(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function update(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'admin_reply' => ['nullable', 'string', 'max:1000'],
        ]);

        $reply = trim((string) ($data['admin_reply'] ?? ''));
        $replyChanged = $reply !== '' && $reply !== (string) $complaint->admin_reply;

        $complaint->forceFill([
            'status' => ComplaintStatus::from($data['status']),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
            'admin_reply' => $reply ?: null,
        ]);

        if ($replyChanged) {
            $complaint->forceFill(['replied_at' => now(), 'replied_by' => $request->user()->id]);
        } elseif ($reply === '') {
            $complaint->forceFill(['replied_at' => null, 'replied_by' => null]);
        }

        $complaint->save();

        return back()->with('notice', 'Saved.'.($replyChanged ? ' The reporter can now read your reply.' : ''));
    }

    public static function tab(string $tab): Builder
    {
        return Complaint::query()->where('status', ComplaintStatus::from($tab));
    }
}
