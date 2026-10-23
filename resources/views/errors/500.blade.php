@extends('layouts.app')

@section('title', 'Something went wrong | ' . config('app.name'))

@push('styles')
    <meta name="robots" content="noindex">
@endpush

@section('content')

<section class="section">
    <div class="container">
        <div class="sent-card">
            <span class="error-code" aria-hidden="true">500</span>
            <h1>Something went wrong</h1>
            <p>Sorry, this page could not load. Please try again in a few minutes. If it keeps happening, contact us.</p>
            <div class="sent-card__actions">
                <a href="{{ route('home') }}" class="btn btn-outline">Go to home</a>
                <a href="{{ route('contact') }}" class="btn btn-primary">Contact us</a>
            </div>
        </div>
    </div>
</section>

@endsection
