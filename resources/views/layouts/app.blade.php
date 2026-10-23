<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', 'Verified online tutors for Grade 1 to A Levels, Matric, FSc and entry tests.')">
    @unless (config('site.indexable'))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Link previews (WhatsApp, Facebook, X) --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', 'Verified online tutors for Grade 1 to A Levels, Matric, FSc and entry tests.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0E6655">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
    @if (app()->getLocale() === 'ur')
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap">
    @endif

    @vite(['resources/css/app.css', 'resources/js/site.js'])
    @stack('styles')
    @stack('schema')
</head>
<body>
    <x-site-header />

    <main class="page-main">
        @yield('content')
    </main>

    <x-site-footer />
    <x-whatsapp-button />

</body>
</html>
