@extends('layouts.dashboard')

@section('title', 'Rate your tutor')

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $review ? 'Edit your review' : 'Rate your tutor' }}</h1>
        <p>{{ $booking->tutorProfile->user->name }} · {{ $booking->subject?->name }} · for {{ $booking->student_name }}. Your review shows on the tutor's profile with your first name only.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">Back to dashboard</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">Please check the highlighted fields.</div>
@endif

<form method="post" action="{{ route('reviews.update', $booking) }}" class="demo-form panel" novalidate>
    @csrf
    @method('PUT')

    <fieldset class="form-section">
        <legend>Your rating</legend>
        <div class="pills">
            @foreach ([5 => '5 – Excellent', 4 => '4 – Very good', 3 => '3 – Good', 2 => '2 – Not great', 1 => '1 – Poor'] as $value => $label)
                <label class="pill">
                    <input type="radio" name="rating" value="{{ $value }}" @checked((int) old('rating', $review?->rating) === $value) required>
                    <span>{{ str_repeat('★', $value) }} {{ \Illuminate\Support\Str::after($label, '– ') }}</span>
                </label>
            @endforeach
        </div>
        @error('rating') <p class="field__error">{{ $message }}</p> @enderror
    </fieldset>

    <div class="field">
        <label for="comment">Your review</label>
        <textarea id="comment" name="comment" rows="5" minlength="{{ \App\Models\Review::MIN_COMMENT }}" maxlength="{{ \App\Models\Review::MAX_COMMENT }}" required
                  class="input @error('comment') is-invalid @enderror" placeholder="How are the classes going? What do you like about the tutor?">{{ old('comment', $review?->comment) }}</textarea>
        @error('comment') <p class="field__error">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="btn btn-primary">{{ $review ? 'Update review' : 'Post review' }}</button>
</form>
@endsection
