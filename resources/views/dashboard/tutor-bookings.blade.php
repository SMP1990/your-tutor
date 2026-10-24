@extends('layouts.dashboard')

@section('title', 'Class bookings')

@section('content')
<div class="dash-head">
    <div>
        <h1>Regular class bookings</h1>
        <p>Families who liked their free demo ask for weekly classes here. Answer within {{ \App\Models\ClassBooking::ANSWER_HOURS }} hours. Times are {{ config('site.timezone_label') }}.</p>
    </div>
</div>

<section class="panel">
    <div class="panel__head"><h2>Waiting for your answer ({{ $waiting->count() }})</h2></div>
    @forelse ($waiting as $booking)
        @php $bag = $errors->getBag('booking'.$booking->id); $mine = $bag->any(); @endphp
        <article class="demo-answer" id="booking-{{ $booking->id }}">
            <div class="item-list__main">
                <strong>{{ $booking->student_name }} · {{ $booking->subject?->name ?? '—' }} · {{ $booking->grade }}</strong>
                <span>{{ $booking->scheduleLabel() }} · from {{ $booking->starts_on->format('D j M') }} · {{ $booking->weeks }} weeks ({{ $booking->classesCount() }} classes) · {{ $booking->rateLabel() }}</span>
                <span>Answer by {{ $booking->expires_at->copy()->timezone(config('site.timezone'))->format('D j M, g:i A') }}</span>
                @if ($booking->notes)<p class="prose-text">“{{ $booking->notes }}”</p>@endif
            </div>

            @if ($mine)
                <div class="form-alert" role="alert">{{ $bag->first() }}</div>
            @endif

            <div class="dash-grid dash-grid--halves">
                <form method="post" action="{{ route('tutor.bookings.accept', $booking) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="meet-{{ $booking->id }}">Google Meet link for these classes</label>
                        <input id="meet-{{ $booking->id }}" name="meet_link" type="url" class="input" required placeholder="https://meet.google.com/abc-defg-hij" value="{{ $mine ? old('meet_link') : '' }}">
                        <p class="field__hint">Use one link for all classes with this student.</p>
                    </div>
                    <div class="field">
                        <label for="note-a-{{ $booking->id }}">Message <span class="field__optional">(optional)</span></label>
                        <input id="note-a-{{ $booking->id }}" name="tutor_note" class="input" maxlength="500">
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm classes</button>
                </form>

                <form method="post" action="{{ route('tutor.bookings.decline', $booking) }}" class="demo-form" novalidate>
                    @csrf
                    <div class="field">
                        <label for="note-d-{{ $booking->id }}">Reason for declining</label>
                        <textarea id="note-d-{{ $booking->id }}" name="tutor_note" rows="3" class="input" maxlength="500" placeholder="e.g. These times are taken; Tue and Thu work."></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline btn-danger" data-confirm-button="Decline this booking?">Decline</button>
                </form>
            </div>
        </article>
    @empty
        <p class="muted">No bookings waiting.</p>
    @endforelse
</section>

<section class="panel">
    <div class="panel__head"><h2>Confirmed students ({{ $confirmed->count() }})</h2></div>
    @if ($confirmed->isEmpty())
        <p class="muted">No confirmed regular classes yet. The class timetable comes in the next update.</p>
    @else
        <ul class="item-list">
            @foreach ($confirmed as $booking)
                <li>
                    <div class="item-list__main">
                        <strong>{{ $booking->student_name }} · {{ $booking->subject?->name ?? '—' }}</strong>
                        <span>{{ $booking->scheduleLabel() }} · from {{ $booking->starts_on->format('D j M') }} · {{ $booking->weeks }} weeks</span>
                    </div>
                    <div class="item-list__side"><a href="{{ $booking->meet_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Google Meet</a></div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@if ($other->isNotEmpty())
    <section class="panel">
        <div class="panel__head"><h2>Earlier requests</h2></div>
        <ul class="item-list">
            @foreach ($other as $booking)
                @php $status = $booking->currentStatus(); @endphp
                <li>
                    <div class="item-list__main"><strong>{{ $booking->student_name }} · {{ $booking->subject?->name ?? '—' }}</strong><span>{{ $booking->scheduleLabel() }}</span></div>
                    <div class="item-list__side"><span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
