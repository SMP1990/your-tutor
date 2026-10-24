<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TutorStatus;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Notifications\TutorApplicationUpdated;
use App\Notifications\TutorChangesReviewed;
use App\Support\DashboardNav;
use App\Support\TutorProfileChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Admin reviews tutor applications (Step 2b). Routes are admin-only with the two-step login. */
class TutorReviewController extends Controller
{
    /** List tabs: key => [label, statuses]. */
    private const TABS = [
        'waiting' => ['Waiting for review', [TutorStatus::Submitted, TutorStatus::InterviewScheduled]],
        'documents_needed' => ['Documents needed', [TutorStatus::DocumentsNeeded]],
        'changes' => ['Profile changes', [TutorStatus::Approved]], // approved tutors with changes waiting
        'approved' => ['Approved', [TutorStatus::Approved]],
        'rejected' => ['Rejected', [TutorStatus::Rejected]],
        'draft' => ['Not submitted yet', [TutorStatus::Draft]],
    ];

    /** Document key in the URL => column. Only these can ever be downloaded. */
    private const DOCUMENTS = ['cnic-front' => 'cnic_front_path', 'cnic-back' => 'cnic_back_path', 'degree' => 'degree_path'];

    private const OPEN = [TutorStatus::Submitted, TutorStatus::InterviewScheduled, TutorStatus::DocumentsNeeded];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'waiting';

        return view('admin.tutors.index', $this->frame($request) + [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn ($t, $key) => ['label' => $t[0], 'count' => $this->tabQuery($key)->count()]),
            'profiles' => $this->tabQuery($tab)->with(['user:id,name,email', 'subjects:id,name'])
                ->when($tab === 'changes', fn ($q) => $q->orderBy('changes_submitted_at'))
                ->orderByRaw('submitted_at is null')->orderBy('submitted_at')
                ->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Request $request, TutorProfile $profile)
    {
        $profile->load(['user', 'subjects', 'reviewer:id,name']);

        return view('admin.tutors.show', $this->frame($request) + [
            'profile' => $profile,
            'documents' => collect(self::DOCUMENTS)->filter(fn ($column) => $profile->{$column})->keys(),
            'open' => in_array($profile->status, self::OPEN, true),
            'pendingSubjects' => Subject::whereIn('id', $profile->pending_changes['subjects'] ?? [])->pluck('name')->all(),
            'pendingDocuments' => collect(self::DOCUMENTS)->filter(fn ($column) => isset($profile->pending_changes['files'][$column]))->keys(),
        ]);
    }

    /** Streams a private document to an admin. Never cached, never a public URL. */
    public function document(Request $request, TutorProfile $profile, string $document)
    {
        $column = self::DOCUMENTS[$document] ?? abort(404);
        $path = ($request->boolean('pending') ? ($profile->pending_changes['files'][$column] ?? null) : $profile->{$column}) ?? abort(404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, "{$document}.".pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store, max-age=0',
        ], 'inline');
    }

    /** Saves subject test / interview details. Scheduling an interview emails the tutor. */
    public function review(Request $request, TutorProfile $profile)
    {
        $this->ensureOpen($profile);

        $data = $request->validate([
            'subject_test_result' => ['nullable', Rule::in(['passed', 'failed'])],
            'interview_at' => ['nullable', 'date'],
            'interview_link' => ['nullable', 'url:https', 'max:255'],
            'interview_result' => ['nullable', Rule::in(['passed', 'failed'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newInterview = filled($data['interview_at'] ?? null)
            && optional($profile->interview_at)->format('Y-m-d H:i') !== date('Y-m-d H:i', strtotime($data['interview_at']));

        $profile->forceFill($data + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        if ($newInterview && $profile->status === TutorStatus::Submitted) {
            $profile->status = TutorStatus::InterviewScheduled;
        }
        $profile->save();

        if ($newInterview) {
            $profile->user->notify(new TutorApplicationUpdated($profile));
        }

        return back()->with('notice', $newInterview ? 'Saved. The tutor has been emailed the interview details.' : 'Saved.');
    }

    /** Approve, reject or ask for documents. */
    public function decide(Request $request, TutorProfile $profile)
    {
        $this->ensureOpen($profile);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'documents_needed'])],
            'decision_note' => ['nullable', 'required_unless:decision,approve', 'string', 'max:1000'],
        ], ['decision_note.required_unless' => 'Write a short message for the tutor.']);

        if ($data['decision'] === 'approve' && ! $profile->canBeApproved()) {
            throw ValidationException::withMessages(['decision' => 'Record a passed subject test and a passed interview before approving.']);
        }

        $status = match ($data['decision']) {
            'approve' => TutorStatus::Approved,
            'reject' => TutorStatus::Rejected,
            'documents_needed' => TutorStatus::DocumentsNeeded,
        };

        $profile->forceFill([
            'status' => $status,
            'decision_note' => $data['decision_note'] ?? null,
            'approved_at' => $status === TutorStatus::Approved ? now() : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        $profile->user->notify(new TutorApplicationUpdated($profile));

        return redirect()->route('admin.tutors.index')
            ->with('notice', "{$profile->user->name}: {$status->label()}. The tutor has been emailed.");
    }

    /** Approve or reject an approved tutor's changes that wait for review (Step 2c-2). */
    public function changes(Request $request, TutorProfile $profile)
    {
        abort_unless($profile->isLive() && $profile->hasPendingChanges(), 409, 'There are no changes waiting for review.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'changes_note' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000'],
        ], ['changes_note.required_if' => 'Write a short message for the tutor.']);

        $approved = $data['decision'] === 'approve';
        $approved ? TutorProfileChanges::approve($profile) : TutorProfileChanges::reject($profile);
        $profile->forceFill(['reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();

        $profile->user->notify(new TutorChangesReviewed($approved, $data['changes_note'] ?? null));

        return redirect()->route('admin.tutors.index', ['tab' => 'changes'])
            ->with('notice', "{$profile->user->name}: changes ".($approved ? 'approved' : 'rejected').'. The tutor has been emailed.');
    }

    private function tabQuery(string $tab)
    {
        return TutorProfile::whereIn('status', self::TABS[$tab][1])
            ->when($tab === 'changes', fn ($q) => $q->whereNotNull('changes_submitted_at'));
    }

    private function ensureOpen(TutorProfile $profile): void
    {
        abort_unless(in_array($profile->status, self::OPEN, true), 409, 'This application is not waiting for a review.');
    }

    private function frame(Request $request): array
    {
        return [
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ];
    }
}
