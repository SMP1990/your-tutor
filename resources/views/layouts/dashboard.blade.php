<!doctype html>
{{--
    DESIGN ONLY dashboard frame with sample data. Child views set:
    $nav  = [['label' => ..., 'icon' => svg paths, 'active' => bool], ...]
    $user = ['name' => ..., 'role' => ...]
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
    @vite(['resources/css/app.css'])
</head>
<body class="dash-body">
    <header class="dash-top">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand__mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 5h7a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H2zM22 5h-7a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h8z"/></svg>
            </span>
            <strong class="brand__name">{{ config('app.name') }}</strong>
        </a>
        <span class="dash-top__role">{{ $user['role'] }}</span>
        <div class="dash-top__user">
            <span class="avatar" aria-hidden="true">{{ collect(explode(' ', $user['name']))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
            <span class="dash-top__name">{{ $user['name'] }}</span>
            <a href="{{ route('home') }}" class="dash-top__logout">Log out</a>
        </div>
    </header>

    <div class="dash-preview" role="note">Preview with sample data. Nothing on this page is real yet.</div>

    <div class="dash">
        <nav class="dash-nav" aria-label="Dashboard">
            <ul>
                @foreach ($nav as $item)
                    <li>
                        <a href="#" @class(['dash-nav__link', 'is-active' => $item['active'] ?? false]) @if ($item['active'] ?? false) aria-current="page" @endif>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <main class="dash-main">
            @yield('content')
        </main>
    </div>
</body>
</html>
