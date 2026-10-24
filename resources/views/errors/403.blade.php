@extends('layouts.app')

@section('title', 'No access | ' . config('app.name'))

@push('styles')
    <meta name="robots" content="noindex">
@endpush

@section('content')

<section class="section">
    <div class="container">
        <div class="sent-card">
            <span class="error-code" aria-hidden="true">403</span>
            <h1>This page is not for your account</h1>
            <p>You are logged in, but this page belongs to a different kind of account.</p>
            <div class="sent-card__actions">
                <a href="{{ route('home') }}" class="btn btn-outline">Go to home</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">My dashboard</a>
                @endauth
            </div>
        </div>
    </div>
</section>

@endsection
