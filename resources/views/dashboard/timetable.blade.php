@extends('layouts.dashboard')

@section('title', $isTutor ? 'My timetable' : 'My classes')

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $isTutor ? 'My timetable' : 'My classes' }}</h1>
        <p>Times are {{ config('site.timezone_label') }}. "Join class" opens {{ \App\Models\ClassSession::JOIN_OPENS_BEFORE }} minutes before each class.
            @unless ($isTutor) You can cancel a class up to {{ \App\Models\ClassSession::FAMILY_CANCEL_HOURS }} hours before it starts. @endunless
        </p>
    </div>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ collect($errors->getBags())->map->first()->filter()->first() }}</div>
@endif

{{-- TUTOR: classes that started and still need attendance (Step 4c) --}}
@if ($isTutor && $toMark->isNotEmpty())
    <section class="panel">
        <div class="panel__head"><h2>Mark attendance ({{ $toMark->count() }})</h2></div>
        <ul class="item-list">
            @foreach ($toMark as $session)
                <li>
                    <div class="item-list__main">
                        <strong>{{ $session->label() }} · {{ $session->booking->student_name }}</strong>
                        <span>{{ $session->booking->subject?->name ?? 'Class' }} · {{ $session->booking->duration_minutes }} min</span>
                    </div>
                    <div class="item-list__side">
                        <form method="post" action="{{ route('classes.mark', $session) }}">
                            @csrf
                            <button type="submit" name="status" value="held" class="btn btn-primary btn-sm">Class held</button>
                            <button type="submit" name="status" value="student_absent" class="btn btn-outline btn-sm">Student absent</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<section class="panel">
    <div class="panel__head"><h2>Upcoming classes</h2></div>
    @include('dashboard.partials.class-list', ['sessions' => $sessions, 'isTutor' => $isTutor, 'withActions' => true])
    @if ($sessions->isNotEmpty())
        {{ $sessions->links('partials.pagination') }}
    @endif
</section>

@if ($past->isNotEmpty())
    <section class="panel">
        <div class="panel__head"><h2>Past and cancelled classes</h2></div>
        <ul class="item-list">
            @foreach ($past as $session)
                <li>
                    <div class="item-list__main">
                        <strong>{{ $session->label() }} · {{ $session->booking->subject?->name ?? 'Class' }}</strong>
                        <span>{{ $isTutor ? $session->booking->student_name : 'With '.($session->booking->tutorProfile?->user?->name ?? 'your tutor').' · for '.$session->booking->student_name }}</span>
                        @if ($session->note)<span>“{{ $session->note }}”</span>@endif
                    </div>
                    <div class="item-list__side"><span class="status status--{{ $session->status->colour() }}">{{ $session->status->label() }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
