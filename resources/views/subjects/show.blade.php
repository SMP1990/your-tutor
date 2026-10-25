@extends('layouts.app')

@section('title', 'Online ' . $subject['name'] . ' tutors in Pakistan | ' . config('app.name'))
{{-- Google shows about 155 characters: shorten the intro, never the "Free demo class." call to action. --}}
@php
    $descStart = 'Find a verified online ' . $subject['name'] . ' tutor for ' . $subject['levels'] . '. ';
    $descEnd = ' Free demo class.';
    $descIntro = \Illuminate\Support\Str::limit($subject['intro'], max(20, 155 - strlen($descStart) - strlen($descEnd)), '…', preserveWords: true);
@endphp
@section('description', $descStart . $descIntro . $descEnd)

@push('schema')
    @include('partials.schema', ['schema' => \App\Support\Schema::breadcrumbs([
        'Home' => route('home'), 'Subjects' => route('subjects.index'), $subject['name'] => null,
    ])])
@endpush

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="{{ route('home') }}">Home</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('subjects.index') }}">Subjects</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $subject['name'] }}</li>
            </ol>
        </nav>
        <h1>Online {{ $subject['name'] }} tutors</h1>
        <p>{{ $subject['intro'] }}</p>
        <ul class="tutor-card__subjects">
            @foreach (explode(' · ', $subject['levels']) as $level)
                <li>{{ $level }}</li>
            @endforeach
        </ul>
    </div>
</section>

{{-- TUTORS --}}
<section class="section">
    <div class="container stack">
        <div class="section-head section-head--row">
            <div class="section-head__text">
                <span class="eyebrow">{{ $subject['name'] }} tutors</span>
                <h2>{{ $tutors ? count($tutors) . ' tutors available' : 'Tutors coming soon' }}</h2>
            </div>
            <a href="{{ route('tutors.index') }}" class="link-strong">All tutors →</a>
        </div>

        @if ($tutors)
            <div class="grid grid--cards">
                @foreach ($tutors as $tutor)
                    <x-tutor-card :tutor="$tutor" />
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <p>We are adding verified {{ $subject['name'] }} tutors. Tell us what you need and we will find a tutor for you.</p>
                <a href="{{ route('contact') }}" class="btn btn-primary">Request a tutor</a>
            </div>
        @endif
    </div>
</section>

{{-- TOPICS --}}
<section class="section section--white">
    <div class="container included">
        <div class="included__list">
            <span class="eyebrow">What tutors cover</span>
            <h2>{{ $subject['name'] }} topics</h2>
            <ul class="topic-list">
                @foreach ($subject['topics'] as $topic)
                    <li>{{ $topic }}</li>
                @endforeach
            </ul>
        </div>
        <div class="info-card included__pay">
            <h3>How it works</h3>
            <ol class="next-steps">
                <li><strong>Pick a tutor</strong><span>Compare profiles, reviews and rates.</span></li>
                <li><strong>Free demo class</strong><span>{{ config('pricing.demo_minutes') }} minutes online, no payment.</span></li>
                <li><strong>Regular classes</strong><span>Pay per session or monthly.</span></li>
            </ol>
            <a href="{{ route('how-it-works') }}" class="link-strong">More about how it works →</a>
        </div>
    </div>
</section>

{{-- OTHER SUBJECTS --}}
<section class="section">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">More subjects</span>
            <h2>Other subjects</h2>
        </div>
        @include('partials.subject-grid', ['subjects' => $others])
    </div>
</section>

@endsection
