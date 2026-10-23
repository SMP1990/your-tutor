@extends('layouts.app')

{{-- Shared frame for Terms, Privacy and Refund Policy. Text is a DRAFT until a lawyer reviews it. --}}

@section('content')

<section class="page-hero">
    <div class="container page-hero__inner">
        <span class="eyebrow">Legal</span>
        <h1>@yield('legal-title')</h1>
        <p>Last updated: {{ config('site.legal_updated') }}</p>
    </div>
</section>

<section class="section">
    <div class="container legal">
        <p class="legal__draft" role="note">Draft: this text is a starting point and has not yet been reviewed by a lawyer.</p>
        <div class="prose legal__body">
            @yield('legal-body')
        </div>
        <nav class="legal__nav" aria-label="Legal pages">
            <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
            <a href="{{ route('privacy') }}">Privacy Policy</a>
            <a href="{{ route('refund-policy') }}">Refund Policy</a>
        </nav>
    </div>
</section>

@endsection
