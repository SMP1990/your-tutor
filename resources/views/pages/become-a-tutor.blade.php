@extends('layouts.app')

@section('title', 'Become an online tutor | ' . config('app.name'))
@section('description', 'Teach online from home in Pakistan. Set your own rate and hours, get students and get paid after every class. Apply as a tutor.')

@php
    $p = config('pricing');
    $ex = $p['tutor_example'];
    $classesPerMonth = $ex['classes_per_day'] * $ex['days_per_week'] * 4;
    $monthly = $ex['rate'] * $classesPerMonth;
    $pkr = fn ($amount) => $p['currency'] . ' ' . number_format($amount);

    $benefits = [
        ['title' => 'Set your own rate', 'body' => 'You decide your hourly fee for each subject and level.', 'icon' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        ['title' => 'Choose your hours', 'body' => 'Mark the times you are free. Teach mornings, evenings or weekends.', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        ['title' => 'Students come to you', 'body' => 'Parents find you through search and book a free demo. No marketing needed.', 'icon' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 11a3 3 0 1 0 0-6M22 21a6 6 0 0 0-4-5.6"/>'],
        ['title' => 'Paid after every class', 'body' => 'Students pay in advance. Your earning is released as soon as the class is done.', 'icon' => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20M6 15h4"/>'],
        ['title' => 'Teach from home', 'body' => 'All classes are online. A laptop or phone with a camera is enough.', 'icon' => '<path d="m3 11 9-8 9 8M5 10v10h14V10"/>'],
        ['title' => 'Grow with reviews', 'body' => 'Good reviews move you up in search and bring more students.', 'icon' => '<path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21.1l1.5-7L2 9.3l7-.8z"/>'],
    ];

    $requirements = [
        'A degree in your subject, or a university student for Grade 1–8 subjects',
        'Your CNIC (for the identity check)',
        'Laptop or smartphone with a working camera and microphone',
        'Stable internet connection',
        'Clear Urdu and/or English',
        'Pass a short subject test and a live interview',
    ];

    $faqs = [
        ['q' => 'Do I have to pay to join?', 'a' => 'No. Applying and creating your profile is free.'],
        ['q' => 'How much does the platform keep?', 'a' => 'A small platform fee is taken from each paid class. The exact fee will be shown before you sign up.'],
        ['q' => 'How many hours must I teach?', 'a' => 'There is no minimum. You choose your own times and can pause your profile when you are busy.'],
        ['q' => 'Do I teach the free demo for free?', 'a' => 'Yes, the first demo with a new student is free. It is your chance to win a regular student.'],
    ];
@endphp

@section('content')

{{-- HERO --}}
<section class="section tutor-hero">
    <div class="container tutor-hero__inner">
        <div class="tutor-hero__text">
            <span class="eyebrow">Become a tutor</span>
            <h1>Teach online. Earn from home.</h1>
            <p>Join our tutor team and teach students across Pakistan. Set your own rate and hours, and get paid after every class.</p>
            <div class="tutor-hero__actions">
                <a href="#apply" class="btn btn-primary">Apply now</a>
                <a href="#earnings" class="btn btn-outline">See what you can earn</a>
            </div>
        </div>
        <div class="earn-card" aria-hidden="true">
            <span class="earn-card__label">This month</span>
            <strong class="earn-card__amount">{{ $pkr($monthly) }}</strong>
            <span class="earn-card__sub">{{ $classesPerMonth }} classes completed</span>
            <ul class="earn-card__rows">
                <li><span>Physics · FSc</span><strong>{{ $pkr($ex['rate']) }}</strong></li>
                <li><span>Maths · O Level</span><strong>{{ $pkr($ex['rate']) }}</strong></li>
                <li><span>Physics · Matric</span><strong>{{ $pkr($ex['rate']) }}</strong></li>
            </ul>
            <span class="earn-card__btn">Withdraw earnings</span>
        </div>
    </div>
</section>

{{-- BENEFITS --}}
<section class="section section--white">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">Why teach with us</span>
            <h2>Everything you need to teach online</h2>
        </div>
        <div class="grid grid--three">
            @foreach ($benefits as $item)
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

{{-- EARNINGS --}}
<section class="section" id="earnings">
    <div class="container earnings">
        <div class="earnings__text">
            <span class="eyebrow">Earnings example</span>
            <h2>What you could earn</h2>
            <p>An example with a rate of {{ $pkr($ex['rate']) }} per hour, {{ $ex['classes_per_day'] }} one-hour classes a day, {{ $ex['days_per_week'] }} days a week.</p>
            <p class="earnings__note">Before the platform fee. Your real income depends on your rate and how many hours you teach.</p>
        </div>
        <dl class="earnings__figures">
            <div><dt>Per day</dt><dd>{{ $pkr($ex['rate'] * $ex['classes_per_day']) }}</dd></div>
            <div><dt>Per week</dt><dd>{{ $pkr($ex['rate'] * $ex['classes_per_day'] * $ex['days_per_week']) }}</dd></div>
            <div class="earnings__month"><dt>Per month</dt><dd>{{ $pkr($monthly) }}</dd></div>
        </dl>
    </div>
</section>

{{-- REQUIREMENTS + PAYOUT --}}
<section class="section section--white">
    <div class="container included">
        <div class="included__list">
            <span class="eyebrow">Requirements</span>
            <h2>What you need</h2>
            <ul class="why__list included__items">
                @foreach ($requirements as $item)
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                        <div><strong>{{ $item }}</strong></div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="info-card included__pay">
            <h3>How you get paid</h3>
            <p>Students pay before the class. When the class is completed, your earning is added to your balance. Withdraw it from your tutor dashboard to:</p>
            <div class="pay-methods">
                <ul>
                    @foreach ($p['payout_methods'] as $method)
                        <li>{{ $method }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- APPLY STEPS --}}
<section class="section section--dark" id="apply">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow eyebrow--accent">How to apply</span>
            <h2>Four steps to your first student</h2>
        </div>
        @include('partials.tutor-steps')
        <div>
            <a href="#" class="btn btn-accent">Start your application</a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section section--white">
    <div class="container faq">
        <div class="section-head">
            <span class="eyebrow">Questions</span>
            <h2>Tutor questions</h2>
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

@endsection
