@extends('layouts.app')

@section('title', 'Pricing | ' . config('app.name'))
@section('description', 'Online tutoring prices in Pakistan: free demo class, pay per session or save with a monthly package. Sibling discount available.')

@php
    $p = config('pricing');
    $pkr = fn ($amount) => $p['currency'] . ' ' . number_format($amount);
    $rate = $p['example_rate'];
    $packageRate = $rate * (100 - $p['package_discount_percent']) / 100;

    $plans = [
        [
            'name' => 'Free demo', 'price' => $pkr(0), 'unit' => 'first class', 'highlight' => false,
            'text' => 'Meet the tutor before you decide.',
            'points' => [$p['demo_minutes'] . '-minute online class', 'One free demo per tutor', 'No payment details needed'],
            'cta' => 'Find a tutor',
        ],
        [
            'name' => 'Pay per session', 'price' => $pkr($p['rate_range'][0]) . '–' . number_format($p['rate_range'][1]), 'unit' => 'per hour', 'highlight' => false,
            'text' => 'Pay the tutor\'s hourly rate for each class you book.',
            'points' => ['Book classes when you need them', 'No contract, stop any time', 'Good for exam revision'],
            'cta' => 'Find a tutor',
        ],
        [
            'name' => 'Monthly package', 'price' => 'Save ' . $p['package_discount_percent'] . '%', 'unit' => 'on every class', 'highlight' => true,
            'text' => 'Fixed weekly timetable with the same tutor.',
            'points' => [implode(', ', $p['package_sizes']) . ' classes a month', 'Monthly progress report for parents', 'Unused classes move to next month'],
            'cta' => 'Find a tutor',
        ],
    ];

    $included = [
        'Verified tutor (ID, degree, subject test, interview)',
        'Live one-to-one video class',
        'Class notes after every session',
        'WhatsApp reminders before class',
        'Attendance in the parent dashboard',
        'Switch tutor at any time',
    ];

    $faqs = [
        ['q' => 'Who sets the hourly rate?', 'a' => 'Each tutor sets their own rate, based on subject, level and experience. You see it on every profile before you book.'],
        ['q' => 'Are there any extra fees?', 'a' => 'No. The price you see on the tutor\'s profile is what you pay per class.'],
        ['q' => 'How does the sibling discount work?', 'a' => 'From the second child in the same family, every class is ' . $p['sibling_discount_percent'] . '% cheaper.'],
        ['q' => 'What happens if a class is missed?', 'a' => 'If the tutor misses a paid class, you get a refund or a free replacement class. Your money is only released to the tutor after the class.'],
    ];
@endphp

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">Pricing</span>
        <h1>Pay only for classes that happen</h1>
        <p>Start with a free demo. Then pay per session or save with a monthly package. Each tutor sets their own hourly rate.</p>
    </div>
</section>

{{-- PLANS --}}
<section class="section">
    <div class="container stack">
        <div class="grid grid--three plans">
            @foreach ($plans as $plan)
                <div class="plan{{ $plan['highlight'] ? ' plan--highlight' : '' }}">
                    @if ($plan['highlight'])
                        <span class="plan__badge">Most popular</span>
                    @endif
                    <h2 class="plan__name">{{ $plan['name'] }}</h2>
                    <p class="plan__price"><strong>{{ $plan['price'] }}</strong> <span>{{ $plan['unit'] }}</span></p>
                    <p class="plan__text">{{ $plan['text'] }}</p>
                    <ul class="plan__points">
                        @foreach ($plan['points'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('tutors.index') }}" class="btn {{ $plan['highlight'] ? 'btn-primary' : 'btn-outline' }}">{{ $plan['cta'] }}</a>
                </div>
            @endforeach
        </div>
        <p class="plans__note">Sibling discount: {{ $p['sibling_discount_percent'] }}% off every class from the second child.</p>
    </div>
</section>

{{-- EXAMPLE --}}
<section class="section section--white">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">Example</span>
            <h2>What a month costs</h2>
            <p class="section-head__lead">A tutor with a rate of {{ $pkr($rate) }} per hour, one-hour classes.</p>
        </div>
        <div class="table-scroll">
            <table class="price-table">
                <thead>
                    <tr>
                        <th scope="col">Classes a month</th>
                        <th scope="col">Pay per session</th>
                        <th scope="col">Monthly package</th>
                        <th scope="col">You save</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($p['package_sizes'] as $classes)
                        <tr>
                            <th scope="row">{{ $classes }} classes <span>({{ $classes / 4 }} a week)</span></th>
                            <td>{{ $pkr($rate * $classes) }}</td>
                            <td><strong>{{ $pkr($packageRate * $classes) }}</strong></td>
                            <td class="price-table__save">{{ $pkr(($rate - $packageRate) * $classes) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- INCLUDED + PAYMENT --}}
<section class="section">
    <div class="container included">
        <div class="included__list">
            <span class="eyebrow">Included in every class</span>
            <h2>No hidden extras</h2>
            <ul class="why__list included__items">
                @foreach ($included as $item)
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                        <div><strong>{{ $item }}</strong></div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="info-card info-card--plain included__pay">
            <h3>How you pay</h3>
            <p>Pay securely online. We hold the payment and release it to the tutor only after the class has taken place.</p>
            <div class="pay-methods">
                <ul>
                    @foreach (['JazzCash', 'Easypaisa', 'Debit / credit card', 'Bank transfer'] as $method)
                        <li>{{ $method }}</li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('how-it-works') }}#payments" class="link-strong">How payments work →</a>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section section--white">
    <div class="container faq">
        <div class="section-head">
            <span class="eyebrow">Questions</span>
            <h2>Pricing questions</h2>
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

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div class="cta-band__text">
                <h2>Try your first class free</h2>
                <p>Book a free demo with any tutor. No payment needed.</p>
            </div>
            <div class="cta-band__actions">
                <a href="{{ route('tutors.index') }}" class="btn btn-accent cta-band__btn">Find a Tutor</a>
            </div>
        </div>
    </div>
</section>

@endsection
