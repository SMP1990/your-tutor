@extends('layouts.app')

@section('title', 'Demo request sent | ' . config('app.name'))

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
            <h1>Request sent{{ session('demo_name') ? ', ' . session('demo_name') : '' }}!</h1>
            <p>{{ rtrim($tutor['name'], '.') }} will reply within {{ \App\Models\DemoRequest::ANSWER_HOURS }} hours. You can follow your request on your dashboard.</p>
            <div class="sent-card__actions">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Go to my dashboard</a>
                <a href="{{ route('tutors.index') }}" class="btn btn-primary">Find more tutors</a>
            </div>
        </div>
    </div>
</section>

@endsection
