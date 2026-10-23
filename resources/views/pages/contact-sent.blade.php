@extends('layouts.app')

@section('title', 'Message sent | ' . config('app.name'))

@push('styles')
    <meta name="robots" content="noindex">
@endpush

@section('content')

<section class="section">
    <div class="container">
        <div class="sent-card">
            <span class="sent-card__icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
            </span>
            <h1>Thank you{{ session('contact_name') ? ', ' . session('contact_name') : '' }}!</h1>
            <p>We have your message and will reply within one working day.</p>
            <div class="sent-card__actions">
                <a href="{{ route('home') }}" class="btn btn-outline">Back to home</a>
                <a href="{{ route('tutors.index') }}" class="btn btn-primary">Find a tutor</a>
            </div>
        </div>
    </div>
</section>

@endsection
