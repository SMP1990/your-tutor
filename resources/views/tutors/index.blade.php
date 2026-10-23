@extends('layouts.app')

@section('title', 'Find a Tutor | ' . config('app.name'))
@section('description', 'Search online tutors by subject, grade, price and rating. Compare profiles and reviews, then book a free demo class.')

@php
    // Static sample data. Replace with real query results later.
    $subjects = [
        ['value' => 'mathematics', 'name' => 'Mathematics', 'on' => false],
        ['value' => 'physics', 'name' => 'Physics', 'on' => true],
        ['value' => 'chemistry', 'name' => 'Chemistry', 'on' => false],
        ['value' => 'biology', 'name' => 'Biology', 'on' => false],
        ['value' => 'english', 'name' => 'English', 'on' => false],
        ['value' => 'computer-science', 'name' => 'Computer Science', 'on' => false],
    ];

    $grades = [
        ['value' => 'grade-1-5', 'name' => 'Grade 1–5', 'on' => false],
        ['value' => 'grade-6-8', 'name' => 'Grade 6–8', 'on' => false],
        ['value' => 'matric', 'name' => 'Matric', 'on' => false],
        ['value' => 'fsc', 'name' => 'FSc / A Level', 'on' => true],
        ['value' => 'o-level', 'name' => 'O Level', 'on' => false],
        ['value' => 'entry-tests', 'name' => 'Entry tests', 'on' => false],
    ];

    $ratings = [
        ['value' => '4.5', 'name' => '4.5 and above', 'on' => true],
        ['value' => '4.0', 'name' => '4.0 and above', 'on' => false],
        ['value' => '3.5', 'name' => '3.5 and above', 'on' => false],
        ['value' => '', 'name' => 'Any rating', 'on' => false],
    ];

    $chips = ['Physics', 'FSc / A Level', 'PKR 1,000–3,000'];

    $sorts = ['Recommended', 'Highest rated', 'Price: low to high', 'Price: high to low'];
@endphp

@section('content')

{{-- PAGE TITLE + SEARCH --}}
<section class="page-title">
    <div class="container page-title__inner">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="{{ url('/') }}">Home</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Find a Tutor</li>
            </ol>
        </nav>
        <div class="page-title__row">
            <div class="page-title__text">
                <h1>Online tutors in Pakistan</h1>
                <p>230 tutors match your search</p>
            </div>
            <form class="tutor-search" role="search" action="{{ url('/tutors') }}" method="get">
                <label for="q" class="sr-only">Search tutors</label>
                <input id="q" name="q" type="search" class="input tutor-search__input" placeholder="Search by name, subject or keyword">
                <button type="submit" class="tutor-search__btn" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>
            </form>
        </div>
    </div>
</section>

{{-- FILTERS + RESULTS --}}
<div class="container listing">

    <div class="sheet-backdrop" data-filters-close hidden></div>

    <aside class="filters" id="filters" aria-label="Filters" data-filters>
        <form class="filters__form" action="{{ url('/tutors') }}" method="get">
            <div class="filters__head">
                <h2>Filters</h2>
                <a href="{{ url('/tutors') }}" class="filters__clear">Clear all</a>
                <button type="button" class="filters__close" aria-label="Close filters" data-filters-close>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <fieldset class="filter-group">
                <legend>Subject</legend>
                @foreach ($subjects as $subject)
                    <label class="check">
                        <input type="checkbox" name="subject[]" value="{{ $subject['value'] }}" @checked($subject['on'])>
                        {{ $subject['name'] }}
                    </label>
                @endforeach
                <a href="#" class="filter-group__more">Show all subjects</a>
            </fieldset>

            <fieldset class="filter-group filter-group--gap">
                <legend>Grade / level</legend>
                <div class="pills">
                    @foreach ($grades as $grade)
                        <label class="pill">
                            <input type="radio" name="grade" value="{{ $grade['value'] }}" @checked($grade['on'])>
                            <span>{{ $grade['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="filter-group filter-group--gap">
                <legend>Price per hour (PKR)</legend>
                <div class="range" aria-hidden="true">
                    <div class="range__track"></div>
                    <div class="range__fill"></div>
                    <div class="range__thumb range__thumb--start"></div>
                    <div class="range__thumb range__thumb--end"></div>
                </div>
                <div class="price-row">
                    <label class="price-row__field">Min
                        <input type="text" name="min" value="1,000" inputmode="numeric" class="price-row__input">
                    </label>
                    <span class="price-row__dash" aria-hidden="true">–</span>
                    <label class="price-row__field">Max
                        <input type="text" name="max" value="3,000" inputmode="numeric" class="price-row__input">
                    </label>
                </div>
            </fieldset>

            <fieldset class="filter-group">
                <legend>Rating</legend>
                @foreach ($ratings as $rating)
                    <label class="check">
                        <input type="radio" name="rating" value="{{ $rating['value'] }}" @checked($rating['on'])>
                        {{ $rating['name'] }}
                    </label>
                @endforeach
            </fieldset>

            <button type="submit" class="btn btn-primary">Show results</button>
        </form>
    </aside>

    <div class="results">
        <div class="results__bar">
            <div class="results__chips">
                <button type="button" class="filters-btn" aria-controls="filters" aria-expanded="false" data-filters-open>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Filters · {{ count($chips) }}
                </button>
                @foreach ($chips as $chip)
                    <a href="#" class="chip" aria-label="Remove filter {{ $chip }}">
                        {{ $chip }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </a>
                @endforeach
            </div>
            <label class="sort">Sort by
                <select name="sort" form="sort-form" class="sort__select" onchange="this.form.submit()">
                    @foreach ($sorts as $sort)
                        <option value="{{ \Illuminate\Support\Str::slug($sort) }}">{{ $sort }}</option>
                    @endforeach
                </select>
            </label>
            <form id="sort-form" action="{{ url('/tutors') }}" method="get"></form>
        </div>

        <div class="grid grid--tutors">
            @foreach ($tutors as $tutor)
                <x-tutor-card :tutor="$tutor" />
            @endforeach
        </div>

        <nav aria-label="Pagination" class="pagination">
            <ul>
                <li><a href="#" class="pagination__link pagination__link--wide">‹ Prev</a></li>
                <li><a href="#" class="pagination__link is-current" aria-current="page">1</a></li>
                <li><a href="#" class="pagination__link">2</a></li>
                <li><a href="#" class="pagination__link">3</a></li>
                <li class="pagination__gap" aria-hidden="true">…</li>
                <li><a href="#" class="pagination__link">39</a></li>
                <li><a href="#" class="pagination__link pagination__link--wide">Next ›</a></li>
            </ul>
        </nav>
    </div>
</div>

@endsection

