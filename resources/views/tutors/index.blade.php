@extends('layouts.app')

@section('title', 'Find a Tutor | ' . config('app.name'))
@section('description', 'Search online tutors by subject, grade, price and rating. Compare profiles and reviews, then book a free demo class.')

@php
    // Chips for the active filters; each links to the same search without that filter.
    $without = function (string $key, ?string $value = null) use ($filters) {
        $params = request()->except(['page', $key]);
        if ($value !== null) {
            $params[$key] = array_values(array_diff($filters['subjects'], [$value]));
        }

        return route('tutors.index', array_filter($params, fn ($v) => $v !== null && $v !== '' && $v !== []));
    };
    $chips = [];
    foreach ($filters['subjects'] as $slug) {
        $chips[] = [$subjects->firstWhere('slug', $slug)?->name ?? $slug, $without('subject', $slug)];
    }
    if ($filters['q'] !== '') $chips[] = ['“'.$filters['q'].'”', $without('q')];
    if ($filters['level']) $chips[] = [$filters['level'], $without('level')];
    if ($filters['min']) $chips[] = ['From PKR '.number_format($filters['min']), $without('min')];
    if ($filters['max']) $chips[] = ['Up to PKR '.number_format($filters['max']), $without('max')];
    if ($filters['rating']) $chips[] = [$filters['rating'].'+ stars', $without('rating')];
@endphp

@section('content')

<section class="page-title">
    <div class="container page-title__inner">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="{{ route('home') }}">Home</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Find a Tutor</li>
            </ol>
        </nav>
        <div class="page-title__row">
            <div class="page-title__text">
                <h1>Online tutors in Pakistan</h1>
                <p>{{ $tutors->total() === 1 ? '1 tutor matches' : $tutors->total().' tutors match' }} your search</p>
            </div>
            <div class="tutor-search" role="search">
                <label for="q" class="sr-only">Search tutors</label>
                <input id="q" name="q" form="filter-form" type="search" class="input tutor-search__input" value="{{ $filters['q'] }}" placeholder="Search by name, subject or keyword">
                <button type="submit" form="filter-form" class="tutor-search__btn" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>

<div class="container listing">

    <div class="sheet-backdrop" data-filters-close hidden></div>

    <aside class="filters" id="filters" aria-label="Filters" data-filters>
        <form class="filters__form" id="filter-form" action="{{ route('tutors.index') }}" method="get">
            <div class="filters__head">
                <h2>Filters</h2>
                <a href="{{ route('tutors.index') }}" class="filters__clear">Clear all</a>
                <button type="button" class="filters__close" aria-label="Close filters" data-filters-close>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <fieldset class="filter-group">
                <legend>Subject</legend>
                @foreach ($subjects as $subject)
                    <label class="check">
                        <input type="checkbox" name="subject[]" value="{{ $subject->slug }}" @checked(in_array($subject->slug, $filters['subjects'], true))>
                        {{ $subject->name }}
                    </label>
                @endforeach
            </fieldset>

            <fieldset class="filter-group filter-group--gap">
                <legend>Grade / level</legend>
                <div class="pills">
                    <label class="pill"><input type="radio" name="level" value="" @checked(! $filters['level'])><span>Any</span></label>
                    @foreach (config('tutors.levels') as $level)
                        <label class="pill"><input type="radio" name="level" value="{{ $level }}" @checked($filters['level'] === $level)><span>{{ $level }}</span></label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="filter-group filter-group--gap">
                <legend>Price per hour (PKR)</legend>
                <div class="price-row">
                    <label class="price-row__field">Min
                        <input type="text" name="min" value="{{ $filters['min'] }}" inputmode="numeric" class="price-row__input" placeholder="{{ number_format(config('tutors.rate_min')) }}">
                    </label>
                    <span class="price-row__dash" aria-hidden="true">–</span>
                    <label class="price-row__field">Max
                        <input type="text" name="max" value="{{ $filters['max'] }}" inputmode="numeric" class="price-row__input" placeholder="{{ number_format(config('tutors.rate_max')) }}">
                    </label>
                </div>
            </fieldset>

            <fieldset class="filter-group">
                <legend>Rating</legend>
                @foreach (\App\Support\TutorSearch::RATINGS as $value => $label)
                    <label class="check"><input type="radio" name="rating" value="{{ $value }}" @checked($filters['rating'] === (string) $value)> {{ $label }}</label>
                @endforeach
                <label class="check"><input type="radio" name="rating" value="" @checked(! $filters['rating'])> Any rating</label>
            </fieldset>

            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <button type="submit" class="btn btn-primary">Show results</button>
        </form>
    </aside>

    <div class="results">
        <div class="results__bar">
            <div class="results__chips">
                <button type="button" class="filters-btn" aria-controls="filters" aria-expanded="false" data-filters-open>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Filters{{ $chips ? ' · '.count($chips) : '' }}
                </button>
                @foreach ($chips as [$label, $href])
                    <a href="{{ $href }}" class="chip" aria-label="Remove filter {{ $label }}">
                        {{ $label }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </a>
                @endforeach
            </div>
            <form method="get" action="{{ route('tutors.index') }}" class="sort">
                @foreach (request()->except(['sort', 'page']) as $key => $value)
                    @foreach ((array) $value as $v)
                        <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">
                    @endforeach
                @endforeach
                <label for="sort">Sort by</label>
                <select id="sort" name="sort" class="sort__select" data-autosubmit>
                    @foreach (\App\Support\TutorSearch::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-outline btn-sm">Sort</button></noscript>
            </form>
        </div>

        @if ($tutors->isEmpty())
            <div class="empty-state">
                <p>No tutors match these filters yet. Try fewer filters, or tell us what you need and we will find a tutor for you.</p>
                <div class="form-actions">
                    <a href="{{ route('tutors.index') }}" class="btn btn-outline">Clear filters</a>
                    <a href="{{ route('contact') }}" class="btn btn-primary">Request a tutor</a>
                </div>
            </div>
        @else
            {{-- The Filters heading is hidden on phones, so the cards (h3) need an h2 above them. --}}
            <h2 class="sr-only">Tutors</h2>
            <div class="grid grid--tutors">
                @foreach ($tutors as $tutor)
                    <x-tutor-card :tutor="$tutor" />
                @endforeach
            </div>
            {{ $tutors->links('partials.pagination') }}
        @endif
    </div>
</div>

@endsection
