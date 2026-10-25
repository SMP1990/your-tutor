@extends('layouts.dashboard')

@section('title', 'Complaint')

@section('content')
@php $b = $complaint->booking; @endphp
<div class="dash-head">
    <div>
        <h1>{{ $complaint->categoryLabel() }}</h1>
        <p>
            <span class="status status--{{ $complaint->status->colour() }}">{{ $complaint->status->label() }}</span>
            Reported {{ $complaint->created_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}
        </p>
    </div>
    <a href="{{ route('admin.complaints.index') }}" class="btn btn-outline btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="dash-grid dash-grid--2">
    <section class="panel">
        <div class="panel__head"><h2>What was reported</h2></div>
        <p class="prose-text">{{ $complaint->details }}</p>
        <dl class="review-list">
            <div><dt>Reported by</dt><dd>
                <a href="{{ route('admin.users.show', $complaint->user) }}">{{ $complaint->user->name }}</a> ({{ $complaint->user->role->label() }})
                · {{ $complaint->user->email }} · {{ $complaint->user->phone ?: 'no phone' }}
            </dd></div>
            <div><dt>Family</dt><dd>
                @if ($b?->user)<a href="{{ route('admin.users.show', $b->user) }}">{{ $b->user->name }}</a> · {{ $b->user->phone ?: 'no phone' }} · student {{ $b->student_name }}@else — @endif
            </dd></div>
            <div><dt>Tutor</dt><dd>
                @if ($b?->tutorProfile)<a href="{{ route('admin.tutors.show', $b->tutorProfile->id) }}">{{ $b->tutorProfile->user->name }}</a>@else — @endif
            </dd></div>
            <div><dt>Booking</dt><dd>{{ $b?->subject?->name ?? 'Class' }} · {{ $b?->scheduleLabel() }}</dd></div>
            @if ($complaint->session)
                <div><dt>Class</dt><dd>{{ $complaint->session->label() }} · {{ $complaint->session->status->label() }}@if ($complaint->session->note) · “{{ $complaint->session->note }}”@endif</dd></div>
            @endif
        </dl>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Your answer</h2></div>
        <form method="post" action="{{ route('admin.complaints.update', $complaint) }}" class="demo-form" novalidate>
            @csrf
            @method('PUT')
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" class="input">
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $complaint->status->value) === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="admin_reply">Reply <span class="field__optional">(the person who reported can read this)</span></label>
                <textarea id="admin_reply" name="admin_reply" rows="4" maxlength="1000" class="input">{{ old('admin_reply', $complaint->admin_reply) }}</textarea>
                @if ($complaint->replied_at)
                    <p class="field__hint">Last reply by {{ $complaint->repliedBy?->name ?? '—' }}, {{ $complaint->replied_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}.</p>
                @endif
            </div>
            <div class="field">
                <label for="admin_note">Private note <span class="field__optional">(only admins see this)</span></label>
                <textarea id="admin_note" name="admin_note" rows="3" maxlength="2000" class="input">{{ old('admin_note', $complaint->admin_note) }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </section>
</div>
@endsection
