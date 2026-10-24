@extends('layouts.dashboard')

@section('title', 'My reviews')

@section('content')
<div class="dash-head">
    <div>
        <h1>My reviews</h1>
        <p>
            @if ($profile?->reviews_count)
                ★ {{ number_format((float) $profile->rating_avg, 1) }} from {{ $profile->reviews_count }} {{ str('review')->plural($profile->reviews_count) }}.
            @endif
            Families can review you after their first class with you has taken place. These reviews show on your public profile.
        </p>
    </div>
    @if ($profile?->hasPublicPage())
        <a href="{{ route('tutors.show', $profile->slug) }}#reviews" class="btn btn-outline btn-sm">View on my profile</a>
    @endif
</div>

<section class="panel">
    @if ($reviews->isEmpty())
        <p class="muted">No reviews yet. Teach great classes and ask families to leave a review from their dashboard.</p>
    @else
        <div class="profile-reviews">
            @foreach ($reviews as $review)
                <x-review-card :review="$review->toCardArray()" />
            @endforeach
        </div>
        {{ $reviews->links('partials.pagination') }}
    @endif
</section>
@endsection
