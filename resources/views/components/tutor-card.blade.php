@props(['tutor'])

@php
    // Accepts an array or an Eloquent model / object.
    $name = data_get($tutor, 'name');
    $photo = data_get($tutor, 'photo');
    $rate = data_get($tutor, 'rate');
    $languages = data_get($tutor, 'languages');

    // Uploads live in storage/; fall back to the placeholder avatar when there is no photo.
    $photoUrl = $photo
        ? (\Illuminate\Support\Str::startsWith($photo, ['http', '/']) ? $photo : asset('storage/' . $photo))
        : asset('images/avatars/' . \Illuminate\Support\Str::slug(\Illuminate\Support\Str::before($name, ' ')) . '.svg');

    $rateLabel = is_numeric($rate) ? 'PKR ' . number_format($rate) : $rate;
    $languagesLabel = is_array($languages) ? implode(', ', $languages) : $languages;
    $slug = data_get($tutor, 'slug');
    $profileUrl = $slug ? route('tutors.show', $slug) : '#';
@endphp

<article {{ $attributes->class('tutor-card') }}>
    <div class="tutor-card__head">
        <img class="tutor-card__photo" src="{{ $photoUrl }}" alt="Photo of {{ $name }}" width="72" height="72">
        <div class="tutor-card__id">
            <div class="tutor-card__name-row">
                <h3 class="tutor-card__name">{{ $name }}</h3>
                <svg width="18" height="18" viewBox="0 0 24 24" role="img" aria-label="Verified tutor"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="m8 12 3 3 5-6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>
            </div>
            <span class="tutor-card__levels">{{ data_get($tutor, 'levels') }}</span>
            <span class="tutor-card__rating">
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21.1l1.5-7L2 9.3l7-.8z" fill="currentColor"/></svg>
                <strong>{{ data_get($tutor, 'rating') }}</strong>
                <span class="tutor-card__muted">({{ data_get($tutor, 'reviews_count') }} reviews)</span>
            </span>
        </div>
    </div>

    <ul class="tutor-card__subjects">
        @foreach (data_get($tutor, 'subjects', []) as $subject)
            <li>{{ $subject }}</li>
        @endforeach
    </ul>

    <p class="tutor-card__bio">{{ data_get($tutor, 'bio') }}</p>

    <dl class="tutor-card__facts">
        <div>
            <dt>Rate from</dt>
            <dd class="tutor-card__rate">{{ $rateLabel }} / hr</dd>
        </div>
        <div>
            <dt>Speaks</dt>
            <dd>{{ $languagesLabel }}</dd>
        </div>
    </dl>

    <div class="tutor-card__actions">
        <a href="{{ $profileUrl }}" class="tutor-card__btn tutor-card__btn--outline">View profile</a>
        <a href="{{ $slug ? $profileUrl . '#book' : '#' }}" class="tutor-card__btn tutor-card__btn--primary">Free demo</a>
    </div>
</article>
