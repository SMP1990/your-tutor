<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Support\DashboardNav;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Step 6c: inbox for messages sent through the public Contact form. Admins answer by WhatsApp or their
 * own email for now (no email is sent by the site), then mark the message answered.
 */
class ContactMessageController extends Controller
{
    public const TABS = [
        'new' => 'New',
        'read' => 'Read',
        'answered' => 'Answered',
    ];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'new';

        return view('admin.contact.index', [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn ($label, $key) => ['label' => $label, 'count' => self::tab($key)->count()]),
            'messages' => self::tab($tab)->latest('id')->paginate(25)->withQueryString(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    /** Opening a new message marks it read. */
    public function show(Request $request, ContactMessage $message)
    {
        if ($message->status === ContactStatus::New) {
            $message->forceFill(['status' => ContactStatus::Read])->save();
        }

        return view('admin.contact.show', [
            'message' => $message->load('user:id,name'),
            'statuses' => ContactStatus::cases(),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function update(Request $request, ContactMessage $message)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ContactStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $message->forceFill([
            'status' => ContactStatus::from($data['status']),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
        ])->save();

        return back()->with('notice', 'Saved.');
    }

    public static function tab(string $tab): Builder
    {
        return ContactMessage::query()->where('status', ContactStatus::from($tab));
    }
}
