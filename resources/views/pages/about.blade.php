@extends('layouts.app')

@section('title', 'About us | ' . config('app.name'))
@section('description', 'We connect students in Pakistan with verified online tutors. Free demo class, clear prices and progress parents can see.')

@php
    $values = [
        ['title' => 'Trust first', 'body' => 'Every tutor is checked: ID, degree, a subject test and a live interview.', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
        ['title' => 'Try before you pay', 'body' => 'A free demo class with every tutor, so families choose with confidence.', 'icon' => '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>'],
        ['title' => 'Progress you can see', 'body' => 'Parents get attendance and class notes after every session.', 'icon' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
        ['title' => 'Fair for tutors', 'body' => 'Tutors set their own rate and are paid after every completed class.', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/>'],
    ];
@endphp

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">About us</span>
        <h1>Good teaching, wherever you live</h1>
        <p>We connect students across Pakistan with verified tutors for live one-to-one online classes, from Grade 1 to A Levels and entry tests.</p>
    </div>
</section>

<section class="section">
    <div class="container about">
        <div class="about__text prose">
            <h2>Why we started</h2>
            <p>Finding a good tutor in Pakistan usually depends on who you know. Many families pay for home tuition without knowing if the tutor is qualified, and students in smaller cities often have no good options at all.</p>
            <p>We built {{ config('app.name') }} to change that. Parents can compare tutors, read real reviews and try a free demo class before paying anything. Students learn from home, and tutors reach students far beyond their own city.</p>
            <h2>Our mission</h2>
            <p>Make trusted, affordable tutoring available to every student in Pakistan, with clear prices and progress parents can follow.</p>
        </div>
        <aside class="info-card info-card--plain about__facts">
            <h3>At a glance</h3>
            <dl>
                <div><dt>Classes</dt><dd>Live, one-to-one, online</dd></div>
                <div><dt>Levels</dt><dd>Grade 1 to A Level, MDCAT, ECAT</dd></div>
                <div><dt>Started</dt><dd>2026, Pakistan</dd></div>
                <div><dt>First class</dt><dd>Free demo with every tutor</dd></div>
            </dl>
        </aside>
    </div>
</section>

<section class="section section--white">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">What we stand for</span>
            <h2>Our values</h2>
        </div>
        <div class="grid grid--four">
            @foreach ($values as $item)
                <div class="info-card">
                    <span class="info-card__icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                    </span>
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div class="cta-band__text">
                <h2>Find your tutor today</h2>
                <p>Compare verified tutors and book a free demo class.</p>
            </div>
            <div class="cta-band__actions">
                <a href="{{ route('tutors.index') }}" class="btn btn-accent cta-band__btn">Find a Tutor</a>
                <a href="{{ route('become-a-tutor') }}" class="btn cta-band__btn cta-band__btn--outline">Become a tutor</a>
            </div>
        </div>
    </div>
</section>

@endsection
