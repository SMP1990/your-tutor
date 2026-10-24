<?php

namespace App\Http\Controllers;

use App\Enums\DemoStatus;
use App\Enums\Role;
use App\Http\Requests\BookDemoRequest;
use App\Models\DemoRequest;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Free demo requests (Step 3a). The tutor's answer comes in Step 3b. */
class DemoBookingController extends Controller
{
    public function create(Request $request, array $tutor)
    {
        $user = $request->user();
        if (! $user) {
            // After logging in or signing up, come straight back to this form.
            $request->session()->put('url.intended', route('tutors.demo', $tutor['slug']));
        }

        return view('tutors.demo', [
            'tutor' => $tutor,
            'account' => $user,
            'canBook' => in_array($user?->role, [Role::Parent, Role::Student], true),
            'children' => $user?->role === Role::Parent ? $user->children()->orderBy('name')->get() : collect(),
            'grades' => BookDemoRequest::GRADES,
            'times' => BookDemoRequest::TIMES,
        ]);
    }

    public function store(BookDemoRequest $request, array $tutor)
    {
        $user = $request->user();
        $child = $user->role === Role::Parent ? $user->children()->findOrFail($request->validated('child_id')) : null;

        $this->checkLimits($user->id, $tutor['id'], $child?->id);

        $demo = new DemoRequest;
        $demo->forceFill([
            'tutor_profile_id' => $tutor['id'],
            'user_id' => $user->id,
            'child_id' => $child?->id,
            'subject_id' => Subject::where('slug', $request->validated('subject'))->value('id'),
            'student_name' => $child?->name ?? $user->name,
            'grade' => $request->validated('grade'),
            'preferred_day' => $request->validated('day'),
            'preferred_time' => $request->validated('time'),
            'phone' => $request->validated('phone'),
            'notes' => $request->validated('notes'),
            'status' => DemoStatus::Pending,
            'expires_at' => now()->addHours(DemoRequest::ANSWER_HOURS),
        ])->save();

        return redirect()
            ->route('tutors.demo.sent', $tutor['slug'])
            ->with('demo_name', strtok($user->name, ' '));
    }

    public function sent(array $tutor)
    {
        return view('tutors.demo-sent', ['tutor' => $tutor]);
    }

    /** The parent or student withdraws a request the tutor has not answered yet. */
    public function cancel(Request $request, DemoRequest $demo)
    {
        abort_unless($demo->user_id === $request->user()->id, 404);
        abort_unless($demo->currentStatus() === DemoStatus::Pending, 409, 'This request can no longer be cancelled.');

        $demo->forceFill(['status' => DemoStatus::Cancelled])->save();

        return back()->with('notice', 'Demo request cancelled.');
    }

    /** Owner rules: one free demo per tutor per learner, and at most MAX_OPEN requests waiting at once. */
    private function checkLimits(int $userId, int $tutorId, ?int $childId): void
    {
        $sameTutor = DemoRequest::active()
            ->where('user_id', $userId)->where('tutor_profile_id', $tutorId)
            ->where('child_id', $childId)
            ->exists();
        if ($sameTutor) {
            throw ValidationException::withMessages(['tutor' => 'You already have a free demo with this tutor'.($childId ? ' for this child' : '').'. Check your dashboard.']);
        }

        if (DemoRequest::open()->where('user_id', $userId)->count() >= DemoRequest::MAX_OPEN) {
            throw ValidationException::withMessages(['tutor' => 'You already have '.DemoRequest::MAX_OPEN.' demo requests waiting for an answer. Please wait for a reply, or cancel one in your dashboard.']);
        }
    }
}
