@extends('layouts.app')

@section('title', 'How it works | ' . config('app.name'))
@section('description', 'How online tutoring works: find a tutor, take a free demo class, start regular classes and pay per session. Also how tutors join.')

@php
    // Static content. Payment and refund wording is general until the final policy is written.
    $familySteps = [
        ['title' => 'Find the right tutor', 'body' => 'Search by subject and grade. Compare profiles, qualifications, reviews and hourly rates side by side.',
         'points' => ['Every tutor is checked before they can teach', 'See availability before you book']],
        ['title' => 'Take a free demo class', 'body' => 'Book a free 30-minute online class. Meet the tutor and see if the teaching style suits the student.',
         'points' => ['No payment needed for the demo', 'We confirm the time with you on WhatsApp']],
        ['title' => 'Start regular classes', 'body' => 'Happy with the demo? Choose pay per session or a monthly package and pick fixed class times.',
         'points' => ['No long contract', 'Switch tutor at any time']],
        ['title' => 'Follow the progress', 'body' => 'Parents see attendance, class notes and tutor feedback after every class in their dashboard.',
         'points' => ['Reminders before every class', 'Session notes after every class']],
    ];

    $classroom = [
        ['title' => 'Live video class', 'body' => 'One-to-one video in the browser on phone or laptop. Nothing to install.', 'icon' => '<rect x="2" y="5" width="14" height="14" rx="2"/><path d="m16 10 6-3v10l-6-3"/>'],
        ['title' => 'Screen share and whiteboard', 'body' => 'Tutors explain on screen, solve numericals step by step and share files.', 'icon' => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>'],
        ['title' => 'Class recordings', 'body' => 'Classes can be recorded for revision. For children, only with parent consent.', 'icon' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3" fill="currentColor"/>'],
        ['title' => 'Notes after class', 'body' => 'The tutor writes what was covered and what to practise before the next class.', 'icon' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>'],
    ];

    $payments = [
        ['title' => 'Pay per session or monthly', 'body' => 'Pay for single classes, or save with a monthly package. Prices are shown on every tutor profile.'],
        ['title' => 'Your money is held safely', 'body' => 'We hold the payment and release it to the tutor only after the class has taken place.'],
        ['title' => 'Refund if a class does not happen', 'body' => 'If a tutor misses a paid class, you get a refund or a free replacement class.'],
    ];

    $faqs = [
        ['q' => 'Is the demo class really free?', 'a' => 'Yes. The first demo class with a tutor is free and needs no payment details.'],
        ['q' => 'What do we need for an online class?', 'a' => 'A phone, tablet or laptop with a camera and a stable internet connection. Earphones help.'],
        ['q' => 'Can parents watch the class?', 'a' => 'Yes. Parents can sit in, and they see attendance and class notes in their dashboard.'],
        ['q' => 'What if we do not like the tutor?', 'a' => 'You can switch to another tutor at any time. Unused paid classes move with you.'],
    ];

    $methods = ['JazzCash', 'Easypaisa', 'Debit / credit card', 'Bank transfer'];
@endphp

@section('content')

{{-- INTRO --}}
<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">How it works</span>
        <h1>Online tutoring, step by step</h1>
        <p>From finding a tutor to the first class in a few days. Here is how it works for families and for tutors.</p>
        <nav class="jump-links" aria-label="On this page">
            <a href="#families" class="chip">For parents &amp; students</a>
            <a href="#classes" class="chip">Online classes</a>
            <a href="#payments" class="chip">Payments</a>
            <a href="#tutors" class="chip">For tutors</a>
        </nav>
    </div>
</section>

{{-- FAMILIES --}}
<section class="section" id="families">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">For parents &amp; students</span>
            <h2>Four steps to the right tutor</h2>
        </div>
        <ol class="timeline">
            @foreach ($familySteps as $step)
                <li class="timeline__item">
                    <span class="timeline__num" aria-hidden="true">{{ $loop->iteration }}</span>
                    <div class="timeline__body">
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['body'] }}</p>
                        <ul class="check-badges">
                            @foreach ($step['points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ol>
        <div>
            <a href="{{ route('tutors.index') }}" class="btn btn-primary">Find a tutor</a>
        </div>
    </div>
</section>

{{-- CLASSES --}}
<section class="section section--white" id="classes">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">Online classes</span>
            <h2>What a class looks like</h2>
        </div>
        <div class="grid grid--four">
            @foreach ($classroom as $item)
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

{{-- PAYMENTS --}}
<section class="section" id="payments">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">Payments</span>
            <h2>Simple, safe payments</h2>
        </div>
        <div class="grid grid--three">
            @foreach ($payments as $item)
                <div class="info-card info-card--plain">
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['body'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="pay-methods">
            <span>Pay with</span>
            <ul>
                @foreach ($methods as $method)
                    <li>{{ $method }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- TUTORS --}}
<section class="section section--dark" id="tutors">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow eyebrow--accent">For tutors</span>
            <h2>Teach online and earn from home</h2>
        </div>
        @include('partials.tutor-steps')
        <div>
            <a href="{{ route('become-a-tutor') }}" class="btn btn-accent">Become a tutor</a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section section--white">
    <div class="container faq">
        <div class="section-head">
            <span class="eyebrow">Questions</span>
            <h2>Common questions</h2>
        </div>
        <div class="faq__list">
            @foreach ($faqs as $faq)
                <details class="faq__item">
                    <summary>{{ $faq['q'] }}</summary>
                    <p>{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="section">
    <div class="container">
        <div class="cta-band">
            <div class="cta-band__text">
                <h2>Book a free demo class today</h2>
                <p>Meet up to three tutors before you decide. No payment needed for the demo.</p>
            </div>
            <div class="cta-band__actions">
                <a href="{{ route('tutors.index') }}" class="btn btn-accent cta-band__btn">Find a Tutor</a>
            </div>
        </div>
    </div>
</section>

@endsection
