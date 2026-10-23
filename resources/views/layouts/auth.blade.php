@extends('layouts.app')

{{-- Shared frame for login pages. DESIGN ONLY: real accounts come with the database step. --}}

@push('styles')
    <meta name="robots" content="noindex">
@endpush

@section('content')

<section class="section auth">
    <div class="container auth__inner">
        <div class="auth-card">
            <div class="auth-card__head">
                <h1>@yield('auth-title')</h1>
                <p>@yield('auth-lead')</p>
            </div>

            @if (session('notice'))
                <div class="form-note" role="status">{{ session('notice') }}</div>
            @endif

            @yield('auth-body')
        </div>

        <aside class="auth-side" aria-label="Why join">
            <h2>Learning that fits your family</h2>
            <ul class="auth-side__list">
                <li>Verified tutors for every subject</li>
                <li>Free demo class with every tutor</li>
                <li>Class notes and attendance for parents</li>
                <li>Pay per session, no contract</li>
            </ul>
        </aside>
    </div>
</section>

@endsection
