@extends('layouts.app')

@section('title', 'FAQs | ' . config('app.name'))
@section('description', 'Answers to common questions about online tutoring, free demo classes, payments, refunds and teaching as a tutor.')

@php
    $p = config('pricing');
    $groups = [
        'families' => ['title' => 'Parents & students', 'items' => [
            ['q' => 'How do I find the right tutor?', 'a' => 'Use Find a Tutor to filter by subject, grade, price and rating. Open a profile to see qualifications, reviews and availability, then book a free demo.'],
            ['q' => 'Is the demo class really free?', 'a' => 'Yes. The first ' . $p['demo_minutes'] . '-minute demo with each tutor is free and needs no payment details.'],
            ['q' => 'What do we need for an online class?', 'a' => 'A phone, tablet or laptop with a camera, and a stable internet connection. Earphones help.'],
            ['q' => 'Can parents watch the class?', 'a' => 'Yes. Parents can sit in, and they see attendance and class notes in their dashboard.'],
            ['q' => 'Are classes recorded?', 'a' => 'Only if you want. Classes for children are recorded only with a parent\'s consent, and only the family and tutor can view them.'],
            ['q' => 'Can we switch tutor?', 'a' => 'Yes, at any time. Unused paid classes move with you to the new tutor.'],
        ]],
        'payments' => ['title' => 'Payments & refunds', 'items' => [
            ['q' => 'How much does a class cost?', 'a' => 'Each tutor sets their own hourly rate, usually ' . $p['currency'] . ' ' . number_format($p['rate_range'][0]) . ' to ' . number_format($p['rate_range'][1]) . '. Monthly packages save ' . $p['package_discount_percent'] . '%.'],
            ['q' => 'How can I pay?', 'a' => 'JazzCash, Easypaisa, debit or credit card, or bank transfer.'],
            ['q' => 'When does the tutor get my money?', 'a' => 'We hold your payment and release it to the tutor only after the class has taken place.'],
            ['q' => 'What if a class does not happen?', 'a' => 'If the tutor misses a paid class, you get a refund or a free replacement class. See the Refund Policy for details.'],
        ]],
        'tutors' => ['title' => 'Tutors', 'items' => [
            ['q' => 'How do I become a tutor?', 'a' => 'Apply online, pass a short subject test and a live interview. Once approved, your profile goes live.'],
            ['q' => 'Does it cost anything to join?', 'a' => 'No. Applying and creating your profile is free. A platform fee is taken from each paid class.'],
            ['q' => 'How do I get paid?', 'a' => 'Your earning is added to your balance after each completed class. Withdraw it to your bank account, JazzCash or Easypaisa.'],
        ]],
    ];
@endphp

@push('schema')
    @include('partials.schema', ['schema' => \App\Support\Schema::faq(array_merge(...array_column($groups, 'items')))])
@endpush

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">Help centre</span>
        <h1>Frequently asked questions</h1>
        <p>Can't find your answer? <a href="{{ route('contact') }}">Contact us</a> and we will help.</p>
        <nav class="jump-links" aria-label="Topics">
            @foreach ($groups as $id => $group)
                <a href="#{{ $id }}" class="chip">{{ $group['title'] }}</a>
            @endforeach
        </nav>
    </div>
</section>

<section class="section">
    <div class="container faq faq--page">
        @foreach ($groups as $id => $group)
            <div class="faq__group" id="{{ $id }}">
                <h2>{{ $group['title'] }}</h2>
                <div class="faq__list">
                    @foreach ($group['items'] as $faq)
                        <details class="faq__item">
                            <summary>{{ $faq['q'] }}</summary>
                            <p>{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

@endsection
