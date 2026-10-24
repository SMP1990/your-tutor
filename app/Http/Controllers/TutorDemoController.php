<?php

namespace App\Http\Controllers;

use App\Enums\DemoStatus;
use App\Models\DemoRequest;
use App\Models\TutorProfile;
use App\Notifications\DemoRequestAnswered;
use App\Support\DashboardNav;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Step 3b: a tutor answers free demo requests: accept (time + Google Meet link) or decline (reason). */
class TutorDemoController extends Controller
{
    public function index(Request $request)
    {
        $profile = $this->profile($request);
        $demos = $profile
            ? $profile->demoRequests()->with('subject:id,name')->latest()->limit(50)->get()
            : collect();

        return view('dashboard.tutor-demos', [
            'waiting' => $demos->filter(fn ($d) => $d->currentStatus() === DemoStatus::Pending),
            'upcoming' => $demos->filter(fn ($d) => $d->status === DemoStatus::Accepted && $d->scheduled_at?->isFuture())
                ->sortBy('scheduled_at'),
            'toConfirm' => $demos->filter(fn ($d) => $d->awaitsOutcome())->sortBy('scheduled_at'),
            'past' => $demos->reject(fn ($d) => $d->currentStatus() === DemoStatus::Pending || $d->status === DemoStatus::Accepted),
            'nav' => DashboardNav::for('tutor'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function accept(Request $request, DemoRequest $demo)
    {
        $this->ensureWaiting($request, $demo);
        $tz = config('site.timezone');

        $data = $request->validateWithBag("demo{$demo->id}", [
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'meet_link' => ['required', 'url:https', 'max:255', 'regex:#^https://meet\.google\.com/[a-z0-9-]+$#i'],
            'tutor_note' => ['nullable', 'string', 'max:500'],
        ], [
            'meet_link.regex' => 'Paste a Google Meet link, e.g. https://meet.google.com/abc-defg-hij',
        ]);

        // Typed in the site timezone (Pakistan time), stored in UTC.
        $at = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], $tz)->utc();
        if ($at->isPast() || $at->gt(now()->addDays(14))) {
            return back()->withInput()->withErrors(['scheduled_at' => 'Choose a time in the next 14 days.'], "demo{$demo->id}");
        }

        $demo->forceFill([
            'status' => DemoStatus::Accepted,
            'scheduled_at' => $at,
            'meet_link' => $data['meet_link'],
            'tutor_note' => $data['tutor_note'] ?? null,
            'answered_at' => now(),
        ])->save();

        $demo->user->notify(new DemoRequestAnswered($demo));

        return redirect()->route('tutor.demos')->with('notice', "Accepted. {$demo->student_name}'s family has been told the time.");
    }

    public function decline(Request $request, DemoRequest $demo)
    {
        $this->ensureWaiting($request, $demo);

        $data = $request->validateWithBag("demo{$demo->id}",
            ['tutor_note' => ['required', 'string', 'max:500']],
            ['tutor_note.required' => 'Write a short reason, e.g. "I am fully booked this month."'],
        );

        $demo->forceFill(['status' => DemoStatus::Declined, 'tutor_note' => $data['tutor_note'], 'answered_at' => now()])->save();
        $demo->user->notify(new DemoRequestAnswered($demo));

        return redirect()->route('tutor.demos')->with('notice', 'Declined. The family has been told.');
    }

    /** After the class time: the tutor says whether the demo happened (Step 3c). */
    public function finish(Request $request, DemoRequest $demo)
    {
        abort_unless($demo->tutor_profile_id === $this->profile($request)?->id, 404);
        abort_unless($demo->awaitsOutcome(), 409, 'You can mark a demo only after its start time.');

        $outcome = $request->validate(['outcome' => ['required', 'in:completed,no_show']])['outcome'];
        $demo->forceFill(['status' => DemoStatus::from($outcome)])->save();

        return redirect()->route('tutor.demos')->with('notice', $outcome === 'completed'
            ? 'Marked as done. Thank you!'
            : 'Marked as "student did not join".');
    }

    /** Only the tutor it was sent to, only while it is still waiting. */
    private function ensureWaiting(Request $request, DemoRequest $demo): void
    {
        abort_unless($demo->tutor_profile_id === $this->profile($request)?->id, 404);
        abort_unless($demo->currentStatus() === DemoStatus::Pending, 409, 'This request is no longer waiting for an answer.');
    }

    private function profile(Request $request): ?TutorProfile
    {
        return $request->user()->tutorProfile()->first();
    }
}
