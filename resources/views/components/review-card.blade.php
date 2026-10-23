@props(['review'])

@php
    $rating = (int) data_get($review, 'rating', 5);
    $initials = data_get($review, 'initials') ?: \Illuminate\Support\Str::of(data_get($review, 'name'))->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('');
@endphp

<figure {{ $attributes->class('review-card') }}>
    <div class="review-card__stars" role="img" aria-label="Rated {{ $rating }} out of 5">
        @for ($i = 0; $i < $rating; $i++)
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21.1l1.5-7L2 9.3l7-.8z" fill="currentColor"/></svg>
        @endfor
    </div>

    <blockquote class="review-card__quote">&ldquo;{{ data_get($review, 'quote') }}&rdquo;</blockquote>

    <figcaption class="review-card__author">
        <span class="review-card__avatar" aria-hidden="true">{{ $initials }}</span>
        <span class="review-card__who">
            <strong>{{ data_get($review, 'name') }}</strong>
            <span>{{ data_get($review, 'role') }}</span>
        </span>
    </figcaption>
</figure>
