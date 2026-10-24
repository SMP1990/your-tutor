@extends('layouts.dashboard')

@section('title', $isTutor ? 'My timetable' : 'My classes')

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $isTutor ? 'My timetable' : 'My classes' }}</h1>
        <p>Upcoming regular classes. Times are {{ config('site.timezone_label') }}. "Join class" opens {{ \App\Models\ClassSession::JOIN_OPENS_BEFORE }} minutes before each class.</p>
    </div>
</div>

<section class="panel">
    @include('dashboard.partials.class-list', ['sessions' => $sessions, 'isTutor' => $isTutor])
    @if ($sessions->isNotEmpty())
        {{ $sessions->links('partials.pagination') }}
    @endif
</section>
@endsection
