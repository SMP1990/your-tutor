@extends('layouts.app')

@section('title', config('app.name') . ' | Find a trusted tutor online')

@php
    // Static sample data. Replace with real data later.
    $steps = [
        ['n' => '1', 'time' => '1 minute', 'title' => 'Tell us what you need', 'body' => 'Pick the subject, grade and the days that suit you.'],
        ['n' => '2', 'time' => 'Your pace', 'title' => 'Compare tutors', 'body' => 'Read profiles, reviews and hourly rates side by side.'],
        ['n' => '3', 'time' => 'Within 24 hours', 'title' => 'Take a free demo', 'body' => 'Meet the tutor online and see if the teaching style fits.'],
        ['n' => '4', 'time' => 'Ongoing', 'title' => 'Start regular classes', 'body' => 'Pay per session through your dashboard. No contracts.'],
    ];

    $tutors = [
        ['name' => 'Ayesha R.', 'subjects' => ['Physics', 'Mathematics'], 'levels' => 'FSc · O Level', 'rating' => '4.9', 'reviews_count' => '38', 'rate' => 'PKR 1,800', 'languages' => 'Urdu, English', 'bio' => 'MSc Physics. Makes numericals simple with step-by-step practice and past papers.'],
        ['name' => 'Hamza S.', 'subjects' => ['Computer Science', 'Python'], 'levels' => 'Matric · A Level', 'rating' => '4.8', 'reviews_count' => '21', 'rate' => 'PKR 2,200', 'languages' => 'English, Urdu', 'bio' => 'Software engineer teaching coding through small projects students build themselves.'],
        ['name' => 'Mehwish T.', 'subjects' => ['Biology', 'Chemistry'], 'levels' => 'FSc · MDCAT', 'rating' => '5.0', 'reviews_count' => '14', 'rate' => 'PKR 2,000', 'languages' => 'Urdu, English, Punjabi', 'bio' => 'Medical student focused on concept clarity and MDCAT-style MCQ practice.'],
    ];

    $subjects = [
        ['name' => 'Mathematics', 'levels' => 'Grade 1 – A Level'], ['name' => 'Physics', 'levels' => 'Matric · FSc · O/A'],
        ['name' => 'Chemistry', 'levels' => 'Matric · FSc · O/A'], ['name' => 'Biology', 'levels' => 'Matric · FSc · MDCAT'],
        ['name' => 'English', 'levels' => 'All grades · IELTS'], ['name' => 'Urdu', 'levels' => 'All grades'],
        ['name' => 'Computer Science', 'levels' => 'Matric · O/A Level'], ['name' => 'Quran & Islamiat', 'levels' => 'All ages'],
        ['name' => 'Accounting', 'levels' => 'ICS · I.Com · A Level'], ['name' => 'Coding for Kids', 'levels' => 'Ages 7–14'],
        ['name' => 'Entry Tests', 'levels' => 'MDCAT · ECAT · NTS'], ['name' => 'Economics', 'levels' => 'O/A Level'],
    ];

    $reviews = [
        ['quote' => 'The free demo helped us pick the right teacher. Our son finally enjoys Maths.', 'name' => 'Sadia Malik', 'role' => 'Parent · Grade 8 Maths', 'initials' => 'SM', 'rating' => 5],
        ['quote' => 'Classes fit around my college timings and I get notes after every session.', 'name' => 'Hassan Raza', 'role' => 'Student · FSc Chemistry', 'initials' => 'HR', 'rating' => 5],
        ['quote' => 'Easy to compare tutors and rates. We knew the fee before the first class.', 'name' => 'Nadia Ahmed', 'role' => 'Parent · O Level English', 'initials' => 'NA', 'rating' => 5],
    ];

    $why = [
        ['title' => 'Verified tutors', 'body' => 'Every tutor passes an ID check, a subject test and a live interview.'],
        ['title' => 'Free demo first', 'body' => 'Try a class with up to three tutors before you pay anything.'],
        ['title' => 'Clear hourly rates', 'body' => 'Tutors show their rate range upfront. No hidden charges.'],
        ['title' => 'Flexible timings', 'body' => 'Morning, evening or weekend slots that work around school.'],
        ['title' => 'Safe for children', 'body' => 'Classes happen on the platform with parent access to session notes.'],
    ];

    $features = [
        ['title' => 'Online classroom', 'body' => 'Video, whiteboard and file sharing in the browser. No installs.', 'icon' => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>'],
        ['title' => 'In-app messaging', 'body' => 'Talk to tutors before you book. Numbers stay private.', 'icon' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/>'],
        ['title' => 'Easy scheduling', 'body' => 'See tutor availability in your time zone and book in a tap.', 'icon' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>'],
        ['title' => 'Progress reports', 'body' => 'Parents get session notes and progress after every class.', 'icon' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
    ];

    $stats = [
        ['10K+', 'Students taught'], ['100+', 'Verified tutors'], ['95+', 'Subjects'], ['4.8', 'Average rating'],
    ];
@endphp

@section('content')

{{-- HERO --}}
<section class="section hero">
    <div class="container hero__inner">
        <div class="hero__text">
            <span class="badge">Free demo class with every tutor</span>
            <h1>Find a trusted tutor for every subject, online.</h1>
            <p class="hero__lead">Verified tutors for Grade 1 to A Levels, Matric, FSc and entry tests. Compare profiles, try a free class, then pay per session.</p>

            <form class="search-box" action="{{ url('/tutors') }}" method="get">
                <div class="search-box__field">
                    <label for="hero-subject">Subject</label>
                    <select id="hero-subject" name="subject[]">
                        <option value="mathematics">Mathematics</option>
                        <option value="physics">Physics</option>
                        <option value="english">English</option>
                    </select>
                </div>
                <div class="search-box__field search-box__field--divided">
                    <label for="hero-grade">Grade / level</label>
                    <select id="hero-grade" name="grade">
                        <option value="matric">Matric (9–10)</option>
                        <option value="fsc">FSc / A Level</option>
                        <option value="o-level">O Level</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary search-box__submit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    Search tutors
                </button>
            </form>

            <dl class="stats">
                @foreach ($stats as [$value, $label])
                    <div>
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="stats__value">{{ $value }}</dd>
                        <dd class="stats__label" aria-hidden="true">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="hero__art" aria-hidden="true">
            <div class="demo-card">
                <div class="demo-card__head">
                    <img src="{{ asset('images/avatars/ayesha.svg') }}" alt="" width="56" height="56">
                    <div>
                        <strong>Ayesha R.</strong>
                        <span>Physics · Mathematics</span>
                    </div>
                </div>
                <div class="demo-card__tags"><span>FSc</span><span>O Level</span><span>Urdu · English</span></div>
                <div class="demo-card__price"><span>4.9 rating</span><strong>PKR 1,800 / hr</strong></div>
                <div class="demo-card__booked">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg>
                    Free demo booked · Tue, 6:00 PM
                </div>
            </div>
            <div class="hero__pill">Verified tutor</div>
        </div>
    </div>
</section>

{{-- HOW IT WORKS --}}
<section class="section section--white" id="how-it-works">
    <div class="container stack stack--lg">
        <div class="section-head">
            <span class="eyebrow">How it works</span>
            <h2>From search to first class in four steps</h2>
        </div>
        <ol class="steps">
            @foreach ($steps as $step)
                <li class="step">
                    <div class="step__top">
                        <span class="step__num">{{ $step['n'] }}</span>
                        <span class="step__time">{{ $step['time'] }}</span>
                    </div>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- FEATURED TUTORS --}}
<section class="section">
    <div class="container stack">
        <div class="section-head section-head--row">
            <div class="section-head__text">
                <span class="eyebrow">Featured tutors</span>
                <h2>Top-rated this month</h2>
            </div>
            <a href="{{ url('/tutors') }}" class="link-strong">View all tutors →</a>
        </div>
        <div class="grid grid--cards">
            @foreach ($tutors as $tutor)
                <x-tutor-card :tutor="$tutor" />
            @endforeach
        </div>
    </div>
</section>

{{-- SUBJECTS --}}
<section class="section section--white" id="subjects">
    <div class="container stack">
        <div class="section-head">
            <span class="eyebrow">Subjects</span>
            <h2>Browse by subject</h2>
        </div>
        <div class="grid grid--subjects">
            @foreach ($subjects as $subject)
                <a href="{{ url('/tutors?subject[]=' . \Illuminate\Support\Str::slug($subject['name'])) }}" class="subject-tile">
                    <strong>{{ $subject['name'] }}</strong>
                    <span>{{ $subject['levels'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- TESTIMONIALS --}}
<section class="section">
    <div class="container stack">
        <div class="section-head section-head--row">
            <div class="section-head__text">
                <span class="eyebrow">Reviews</span>
                <h2>What parents and students say</h2>
            </div>
            <div class="slider-nav">
                <button type="button" class="slider-btn" aria-label="Previous reviews" data-slider-prev>‹</button>
                <button type="button" class="slider-btn slider-btn--primary" aria-label="Next reviews" data-slider-next>›</button>
            </div>
        </div>
        <div class="slider" data-slider tabindex="0" aria-label="Reviews">
            @foreach ($reviews as $review)
                <div class="slider__item">
                    <x-review-card :review="$review" />
                </div>
            @endforeach
        </div>
        <div class="slider-dots" aria-hidden="true" data-slider-dots>
            @foreach ($reviews as $review)
                <span class="slider-dots__dot{{ $loop->first ? ' is-active' : '' }}"></span>
            @endforeach
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="section">
    <div class="container">
        <div class="cta-band">
            <div class="cta-band__text">
                <h2>Book a free demo class today</h2>
                <p>Meet up to three tutors before you decide. No payment needed for the demo.</p>
            </div>
            <div class="cta-band__actions">
                <a href="{{ url('/tutors') }}" class="btn btn-accent cta-band__btn">Find a Tutor</a>
                <a href="#" class="btn cta-band__btn cta-band__btn--outline">Become a tutor</a>
            </div>
        </div>
    </div>
</section>

{{-- WHY CHOOSE US --}}
<section class="section section--dark">
    <div class="container why">
        <div class="why__main">
            <span class="eyebrow eyebrow--accent">Why choose us</span>
            <h2>Built for parents who want results, not surprises</h2>
            <ul class="why__list">
                @foreach ($why as $point)
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                        <div>
                            <strong>{{ $point['title'] }}</strong>
                            <span>{{ $point['body'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="why__features">
            @foreach ($features as $feature)
                <div class="feature-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">{!! $feature['icon'] !!}</svg>
                    <strong>{{ $feature['title'] }}</strong>
                    <span>{{ $feature['body'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    (function () {
        var track = document.querySelector('[data-slider]');
        if (!track) return;
        var items = track.children;
        var dots = document.querySelectorAll('[data-slider-dots] .slider-dots__dot');
        var step = function () { return items.length > 1 ? items[1].offsetLeft - items[0].offsetLeft : track.clientWidth; };

        document.querySelector('[data-slider-prev]').addEventListener('click', function () {
            track.scrollBy({ left: -step(), behavior: 'smooth' });
        });
        document.querySelector('[data-slider-next]').addEventListener('click', function () {
            track.scrollBy({ left: step(), behavior: 'smooth' });
        });
        track.addEventListener('scroll', function () {
            var i = Math.round(track.scrollLeft / step());
            dots.forEach(function (d, n) { d.classList.toggle('is-active', n === i); });
        }, { passive: true });
    })();
</script>
@endpush
