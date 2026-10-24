@extends('layouts.dashboard')

@section('title', 'Review: '.$profile->user->name)

@php
    $days = config('tutors.days');
    $slots = config('tutors.time_slots');
    $docLabels = ['cnic-front' => 'CNIC front', 'cnic-back' => 'CNIC back', 'degree' => 'Degree'];
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $profile->user->name }}</h1>
        <p>
            <span class="status status--grey">{{ $profile->status->label() }}</span>
            Submitted {{ $profile->submitted_at?->format('d M Y, g:i A') ?? '—' }}
            @if ($profile->reviewer) · last reviewed by {{ $profile->reviewer->name }}, {{ $profile->reviewed_at->diffForHumans() }} @endif
        </p>
    </div>
    <a href="{{ route('admin.tutors.index') }}" class="btn btn-outline btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="dash-grid dash-grid--2">
    {{-- APPLICATION --}}
    <section class="panel">
        <div class="review-head">
            @if ($profile->photo_path)
                <img src="{{ asset('storage/'.$profile->photo_path) }}" alt="" width="88" height="88" class="application__photo">
            @endif
            <div>
                <h2 class="review-head__title">{{ $profile->headline ?: 'No headline' }}</h2>
                <p class="muted">{{ $profile->city ?: '—' }} · {{ $profile->experience_years ?? '—' }} years teaching · {{ $profile->user->email }} · {{ $profile->user->phone ?: 'no phone' }}</p>
            </div>
        </div>

        <dl class="review-list">
            <div><dt>Subjects</dt><dd>{{ $profile->subjects->pluck('name')->implode(', ') ?: '—' }}</dd></div>
            <div><dt>Levels</dt><dd>{{ $profile->levels ?: '—' }}</dd></div>
            <div><dt>Languages</dt><dd>{{ $profile->languages ?: '—' }}</dd></div>
            <div><dt>Hourly rate</dt><dd>{{ $profile->hourlyRate() ? $profile->currency.' '.number_format($profile->hourlyRate()) : '—' }}</dd></div>
            <div><dt>Intro video</dt><dd>@if ($profile->intro_video_url)<a href="{{ $profile->intro_video_url }}" target="_blank" rel="noopener noreferrer">Open video</a>@else — @endif</dd></div>
            <div><dt>Education</dt><dd>
                @forelse ($profile->education ?? [] as $e)
                    {{ $e['degree'] }}, {{ $e['institute'] }}{{ ! empty($e['year']) ? ', '.$e['year'] : '' }}<br>
                @empty — @endforelse
            </dd></div>
            <div><dt>Available</dt><dd>
                @php $free = collect($days)->map(fn ($d) => ($profile->availability[$d] ?? []) ? $d.': '.collect($profile->availability[$d])->map(fn ($s) => $slots[$s] ?? $s)->implode(', ') : null)->filter(); @endphp
                {!! $free->isEmpty() ? '—' : $free->map(fn ($l) => e($l))->implode('<br>') !!}
            </dd></div>
        </dl>

        <div>
            <h3 class="review-sub">About</h3>
            <p class="prose-text">{{ $profile->bio ?: '—' }}</p>
        </div>

        <div>
            <h3 class="review-sub">Documents (private)</h3>
            <div class="child-tabs">
                @foreach ($docLabels as $key => $label)
                    @if ($documents->contains($key))
                        <a href="{{ route('admin.tutors.document', [$profile->id, $key]) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View {{ $label }}</a>
                    @else
                        <span class="status status--red">{{ $label }} missing</span>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- REVIEW + DECISION --}}
    <div class="review-side">
        @if ($open)
            <section class="panel">
                <div class="panel__head"><h2>1. Test and interview</h2></div>
                <form method="post" action="{{ route('admin.tutors.review', $profile->id) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="subject_test_result">Subject test</label>
                        <select id="subject_test_result" name="subject_test_result" class="input">
                            <option value="">Not yet</option>
                            <option value="passed" @selected(old('subject_test_result', $profile->subject_test_result) === 'passed')>Passed</option>
                            <option value="failed" @selected(old('subject_test_result', $profile->subject_test_result) === 'failed')>Failed</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="interview_at">Interview date and time</label>
                        <input id="interview_at" name="interview_at" type="datetime-local" class="input" value="{{ old('interview_at', $profile->interview_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="field">
                        <label for="interview_link">Google Meet link</label>
                        <input id="interview_link" name="interview_link" type="url" class="input" placeholder="https://meet.google.com/…" value="{{ old('interview_link', $profile->interview_link) }}">
                        <p class="field__hint">Setting a new date emails the tutor the time and link.</p>
                    </div>
                    <div class="field">
                        <label for="interview_result">Interview result</label>
                        <select id="interview_result" name="interview_result" class="input">
                            <option value="">Not yet</option>
                            <option value="passed" @selected(old('interview_result', $profile->interview_result) === 'passed')>Passed</option>
                            <option value="failed" @selected(old('interview_result', $profile->interview_result) === 'failed')>Failed</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="admin_notes">Private notes <span class="field__optional">(only admins see these)</span></label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" class="input">{{ old('admin_notes', $profile->admin_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-outline">Save</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel__head"><h2>2. Decision</h2></div>
                @unless ($profile->canBeApproved())
                    <p class="muted">Approve becomes available once the subject test and the interview are both marked as passed.</p>
                @endunless
                <form method="post" action="{{ route('admin.tutors.decide', $profile->id) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="decision_note">Message to the tutor <span class="field__optional">(required for reject or documents needed)</span></label>
                        <textarea id="decision_note" name="decision_note" rows="3" class="input" placeholder="e.g. Please upload a clearer photo of the back of your CNIC.">{{ old('decision_note') }}</textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="decision" value="approve" class="btn btn-primary" @disabled(! $profile->canBeApproved())>Approve</button>
                        <button type="submit" name="decision" value="documents_needed" class="btn btn-outline">Ask for documents</button>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="decision" value="reject" class="btn btn-outline btn-danger" data-confirm-button="Reject this application?">Reject</button>
                    </div>
                </form>
            </section>
        @else
            <section class="panel">
                <div class="panel__head"><h2>Decision</h2><span class="status status--grey">{{ $profile->status->label() }}</span></div>
                @if ($profile->decision_note)<p>Message sent: “{{ $profile->decision_note }}”</p>@endif
                <p class="muted">Test: {{ $profile->subject_test_result ?? 'not recorded' }} · Interview: {{ $profile->interview_result ?? 'not recorded' }}</p>
                @if ($profile->hasPublicPage())
                    <a href="{{ route('tutors.show', $profile->slug) }}" class="btn btn-outline btn-sm">View public profile</a>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
