@extends('layouts.app')

@section('title', 'Page not found | ' . config('app.name'))

@push('styles')
    <meta name="robots" content="noindex">
@endpush

@section('content')

<section class="section">
    <div class="container">
        <div class="sent-card">
            <span class="error-code" aria-hidden="true">404</span>
            <h1>Page not found</h1>
            <p>The page you are looking for does not exist or has moved.</p>
            <div class="sent-card__actions">
                <a href="{{ route('home') }}" class="btn btn-outline">Go to home</a>
                <a href="{{ route('tutors.index') }}" class="btn btn-primary">Find a tutor</a>
            </div>
        </div>
    </div>
</section>

@endsection
