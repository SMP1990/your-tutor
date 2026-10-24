@extends('layouts.dashboard')

@section('title', 'Demo requests')

@php
    $times = \App\Http\Requests\BookDemoRequest::TIMES;
    $tzLabel = config('site.timezone_label');
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Free demo requests</h1>
        <p>Answer each request within {{ \App\Models\DemoRequest::ANSWER_HOURS }} hours, or it expires.</p>
    </div>
</div>

{{-- WAITING --}}
<section class="panel">
    <div class="panel__head"><h2>Waiting for your answer ({{ $waiting->count() }})</h2></div>
    @forelse ($waiting as $demo)
        @php $bag = $errors->getBag('demo'.$demo->id); $mine = $bag->any(); @endphp
        <article class="demo-answer" id="demo-{{ $demo->id }}">
            <div class="item-list__main">
                <strong>{{ $demo->student_name }} · {{ $demo->subject?->name ?? '—' }} · {{ $demo->grade }}</strong>
                <span>Prefers {{ $demo->preferred_day }} {{ strtolower($times[$demo->preferred_time] ?? $demo->preferred_time) }} · answer by {{ $demo->expires_at->copy()->timezone(config('site.timezone'))->format('D j M, g:i A') }}</span>
                @if ($demo->notes)<p class="prose-text">“{{ $demo->notes }}”</p>@endif
            </div>

            @if ($mine)
                <div class="form-alert" role="alert">{{ $bag->first() }}</div>
            @endif

            <div class="dash-grid dash-grid--halves">
                <form method="post" action="{{ route('tutor.demos.accept', $demo) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="at-{{ $demo->id }}">Date and time ({{ $tzLabel }})</label>
                        <input id="at-{{ $demo->id }}" name="scheduled_at" type="datetime-local" class="input" required value="{{ $mine ? old('scheduled_at') : '' }}">
                    </div>
                    <div class="field">
                        <label for="meet-{{ $demo->id }}">Google Meet link</label>
                        <input id="meet-{{ $demo->id }}" name="meet_link" type="url" class="input" required placeholder="https://meet.google.com/abc-defg-hij" value="{{ $mine ? old('meet_link') : '' }}">
                    </div>
                    <div class="field">
                        <label for="note-a-{{ $demo->id }}">Message <span class="field__optional">(optional)</span></label>
                        <input id="note-a-{{ $demo->id }}" name="tutor_note" class="input" maxlength="500" placeholder="e.g. Please keep the textbook ready.">
                    </div>
                    <button type="submit" class="btn btn-primary">Accept</button>
                </form>

                <form method="post" action="{{ route('tutor.demos.decline', $demo) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="note-d-{{ $demo->id }}">Reason for declining</label>
                        <textarea id="note-d-{{ $demo->id }}" name="tutor_note" rows="3" class="input" maxlength="500" placeholder="e.g. I am fully booked this month."></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline btn-danger" data-confirm-button="Decline this request?">Decline</button>
                </form>
            </div>
        </article>
    @empty
        <p class="muted">No requests waiting. New requests appear here when parents or students book a free demo with you.</p>
    @endforelse
</section>

{{-- UPCOMING --}}
<section class="panel">
    <div class="panel__head"><h2>Upcoming demos ({{ $upcoming->count() }})</h2></div>
    @if ($upcoming->isEmpty())
        <p class="muted">No upcoming demos.</p>
    @else
        <ul class="item-list">
            @foreach ($upcoming as $demo)
                <li>
                    <div class="item-list__main">
                        <strong>{{ $demo->scheduledLabel() }} · {{ $demo->student_name }}</strong>
                        <span>{{ $demo->subject?->name ?? '—' }} · {{ $demo->grade }}</span>
                    </div>
                    <div class="item-list__side">
                        <a href="{{ $demo->meet_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">Open Google Meet</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

{{-- PAST --}}
@if ($past->isNotEmpty())
    <section class="panel">
        <div class="panel__head"><h2>Earlier requests</h2></div>
        <ul class="item-list">
            @foreach ($past as $demo)
                @php $status = $demo->currentStatus(); @endphp
                <li>
                    <div class="item-list__main">
                        <strong>{{ $demo->student_name }} · {{ $demo->subject?->name ?? '—' }}</strong>
                        <span>{{ $demo->scheduledLabel() ?? 'Sent '.$demo->created_at->format('d M') }}</span>
                    </div>
                    <div class="item-list__side"><span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
