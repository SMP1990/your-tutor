@extends('layouts.app')

@section('title', $tutor['name'] . ' – Online ' . implode(' & ', $tutor['subjects']) . ' tutor | ' . config('app.name'))
@section('description', $tutor['headline'] . '. ' . $tutor['bio'] . ' Book a free demo class.')

@php
    $slots = ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'];
    $firstName = \Illuminate\Support\Str::before($tutor['name'], ' ');
    $photo = asset('images/avatars/' . \Illuminate\Support\Str::slug($firstName) . '.svg');
@endphp

@section('content')

<div class="container profile-crumbs">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('tutors.index') }}">Find a Tutor</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">{{ $tutor['name'] }}</li>
        </ol>
    </nav>
</div>

<div class="container profile">
    <div class="profile__main">

        {{-- INTRO --}}
        <section class="profile-card profile-intro">
            <img class="profile-intro__photo" src="{{ $photo }}" alt="Photo of {{ $tutor['name'] }}" width="120" height="120">
            <div class="profile-intro__text">
                <div class="profile-intro__name-row">
                    <h1>{{ $tutor['name'] }}</h1>
                    <span class="verified-pill">
                        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="m8 12 3 3 5-6" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/></svg>
                        Verified tutor
                    </span>
                </div>
                <p class="profile-intro__headline">{{ $tutor['headline'] }}</p>
                <ul class="profile-facts">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21.1l1.5-7L2 9.3l7-.8z" fill="#F0A53A"/></svg>
                        <strong>{{ $tutor['rating'] }}</strong>&nbsp;({{ $tutor['reviews_count'] }} reviews)
                    </li>
                    <li>{{ $tutor['experience_years'] }} years teaching</li>
                    <li>{{ $tutor['students_taught'] }}+ students</li>
                    <li>{{ $tutor['city'] }}</li>
                    <li>Speaks {{ $tutor['languages'] }}</li>
                </ul>
                <ul class="tutor-card__subjects">
                    @foreach ($tutor['subjects'] as $subject)
                        <li>{{ $subject }}</li>
                    @endforeach
                    <li class="chip-neutral">{{ $tutor['levels'] }}</li>
                </ul>
            </div>
        </section>

        {{-- VIDEO --}}
        <section class="profile-card" aria-labelledby="video-title">
            <h2 id="video-title" class="profile-card__title">Intro video</h2>
            <div class="video-placeholder">
                <span class="video-placeholder__play" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </span>
                <p>Meet {{ $firstName }} in a 1-minute video.<br><span>Video coming soon</span></p>
            </div>
        </section>

        {{-- ABOUT --}}
        <section class="profile-card" aria-labelledby="about-title">
            <h2 id="about-title" class="profile-card__title">About {{ $firstName }}</h2>
            <div class="prose">
                @foreach ($tutor['about'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </section>

        {{-- QUALIFICATIONS --}}
        <section class="profile-card" aria-labelledby="qual-title">
            <h2 id="qual-title" class="profile-card__title">Qualifications</h2>
            <ul class="qual-list">
                @foreach ($tutor['qualifications'] as $q)
                    <li>
                        <span class="qual-list__icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></svg>
                        </span>
                        <div>
                            <strong>{{ $q['title'] }}</strong>
                            <span>{{ $q['place'] }} · {{ $q['year'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
            <ul class="check-badges">
                <li>ID checked</li>
                <li>Degree checked</li>
                <li>Subject test passed</li>
                <li>Live interview</li>
            </ul>
        </section>

        {{-- AVAILABILITY --}}
        <section class="profile-card" aria-labelledby="avail-title">
            <h2 id="avail-title" class="profile-card__title">Availability</h2>
            <p class="profile-card__note">Pakistan time (PKT). Exact slots are agreed after the free demo.</p>
            <div class="avail-scroll">
                <table class="avail">
                    <thead>
                        <tr>
                            <th scope="col"><span class="sr-only">Time of day</span></th>
                            @foreach ($tutor['availability'] as $day => $daySlots)
                                <th scope="col">{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slots as $key => $label)
                            <tr>
                                <th scope="row">{{ $label }}</th>
                                @foreach ($tutor['availability'] as $day => $daySlots)
                                    <td>
                                        @if (in_array($key, $daySlots, true))
                                            <span class="avail__on" title="Available"><span class="sr-only">Available</span></span>
                                        @else
                                            <span class="avail__off"><span class="sr-only">Not available</span></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- REVIEWS --}}
        <section class="profile-card" aria-labelledby="reviews-title" id="reviews">
            <h2 id="reviews-title" class="profile-card__title">Reviews</h2>
            <div class="rating-summary">
                <div class="rating-summary__score">
                    <strong>{{ $tutor['rating'] }}</strong>
                    <span>out of 5</span>
                    <span>{{ $tutor['reviews_count'] }} reviews</span>
                </div>
                <ul class="rating-bars">
                    @foreach ($tutor['rating_breakdown'] as $stars => $percent)
                        <li>
                            <span>{{ $stars }} star</span>
                            <progress class="rating-bars__track" value="{{ $percent }}" max="100">{{ $percent }}%</progress>
                            <span>{{ $percent }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="profile-reviews">
                @foreach ($tutor['reviews'] as $review)
                    <x-review-card :review="$review" />
                @endforeach
            </div>
        </section>
    </div>

    {{-- BOOKING SIDEBAR --}}
    <aside class="profile__side" id="book" aria-label="Book {{ $firstName }}">
        <div class="book-card">
            <div class="book-card__price">
                <span>Rate from</span>
                <strong>{{ $tutor['rate'] }} <small>/ hr</small></strong>
            </div>
            <p class="book-card__free">First demo class is <strong>free</strong></p>
            <a href="#" class="btn btn-primary book-card__btn">Book free demo</a>
            <a href="{{ route('tutors.index') }}" class="btn btn-outline book-card__btn">Compare other tutors</a>
            <ul class="book-card__trust">
                <li>Pay per session, no contract</li>
                <li>Switch tutor any time</li>
                <li>Refund if a class does not happen</li>
            </ul>
        </div>
    </aside>
</div>

{{-- SIMILAR TUTORS --}}
@if ($similar)
    <section class="section section--white">
        <div class="container stack">
            <div class="section-head">
                <span class="eyebrow">Similar tutors</span>
                <h2>More {{ $tutor['subjects'][0] }} tutors</h2>
            </div>
            <div class="grid grid--cards">
                @foreach ($similar as $other)
                    <x-tutor-card :tutor="$other" />
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- MOBILE BOOKING BAR --}}
<div class="book-bar">
    <div>
        <strong>{{ $tutor['rate'] }} / hr</strong>
        <span>Free demo class</span>
    </div>
    <a href="#" class="btn btn-primary">Book free demo</a>
</div>

@endsection
