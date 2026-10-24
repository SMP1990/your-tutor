@extends('layouts.dashboard')

@section('title', 'Tutor dashboard')

@php
    $status = $profile?->status;
    $statusColour = match ($status) {
        \App\Enums\TutorStatus::Approved => 'green',
        \App\Enums\TutorStatus::Rejected => 'red',
        null, \App\Enums\TutorStatus::Draft => 'grey',
        default => 'amber',
    };
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Welcome, {{ strtok($account->name, ' ') }}</h1>
        <p>
            Application: <span class="status status--{{ $statusColour }}">{{ $status?->label() ?? 'Not started' }}</span>
        </p>
    </div>
    @if ($profile?->hasPublicPage())
        <a href="{{ route('tutors.show', $profile->slug) }}" class="btn btn-outline btn-sm">View my public profile</a>
    @endif
</div>

@include('dashboard.partials.application-message', ['profile' => $profile])

<div class="dash-grid dash-grid--halves">
    @if ($status === \App\Enums\TutorStatus::Approved)
        <section class="panel">
            <div class="panel__head"><h2>My profile</h2><span class="status status--green">Live</span></div>
            <ul class="item-list">
                <li><div class="item-list__main"><strong>Subjects</strong><span>{{ $profile->subjects->pluck('name')->implode(', ') ?: 'None yet' }}</span></div></li>
                <li><div class="item-list__main"><strong>Hourly rate</strong><span>{{ $profile->hourlyRate() ? $profile->currency.' '.number_format($profile->hourlyRate()) : 'Not set' }}</span></div></li>
                <li><div class="item-list__main"><strong>City</strong><span>{{ $profile->city ?: 'Not set' }}</span></div></li>
            </ul>
            @if ($profile->hasPendingChanges())
                <p class="muted">Some changes are waiting for our team to approve them.</p>
            @endif
            <div><a href="{{ route('tutor.application') }}" class="btn btn-primary btn-sm">Edit my profile</a></div>
        </section>
    @else
        <section class="panel">
            <div class="panel__head"><h2>Your tutor application</h2><span class="status status--{{ $statusColour }}">{{ $status?->label() ?? 'Not started' }}</span></div>
            @if (! $profile || $profile->isEditableByTutor())
                <p class="muted">Tell us what you teach, your rate and when you are free, and upload your CNIC and degree. You can save and finish later.</p>
                <div><a href="{{ route('tutor.application') }}" class="btn btn-primary btn-sm">{{ $profile ? 'Continue application' : 'Start application' }}</a></div>
            @elseif ($status === \App\Enums\TutorStatus::Rejected)
                <p class="muted">This application was not approved.</p>
                <div><a href="{{ route('tutor.application') }}" class="btn btn-outline btn-sm">View application</a></div>
            @else
                <p class="muted">Thank you! Your application is being reviewed. We will email you about the next step.</p>
                <div><a href="{{ route('tutor.application') }}" class="btn btn-outline btn-sm">View application</a></div>
            @endif
        </section>
    @endif

    @if ($status === \App\Enums\TutorStatus::Approved)
        @php $waitingDemos = $profile->demoRequests()->open()->count(); @endphp
        <section class="panel">
            <div class="panel__head">
                <h2>Free demo requests</h2>
                @if ($waitingDemos)<span class="status status--amber">{{ $waitingDemos }} waiting</span>@endif
            </div>
            <p class="muted">{{ $waitingDemos ? 'Please answer within '.\App\Models\DemoRequest::ANSWER_HOURS.' hours, or the request expires.' : 'No requests waiting right now.' }}</p>
            <div><a href="{{ route('tutor.demos') }}" class="btn btn-primary btn-sm">Open demo requests</a></div>
        </section>
    @else
        @include('dashboard.partials.coming-soon', [
            'title' => 'Classes and earnings',
            'text' => 'Demo requests, your timetable and earnings will show here once you are approved.',
        ])
    @endif
</div>

@include('dashboard.partials.account')
@endsection
