<!doctype html>
{{--
    Dashboard frame (sidebar + top bar). Child views set:
    $nav  = [['label' => ..., 'icon' => svg paths, 'active' => bool], ...]
    $user = ['name' => ..., 'role' => ...]
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('app.name') }}</title>
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/site.js'])
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
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dash-top__logout link-button">Log out</button>
            </form>
        </div>
    </header>

    <div class="dash">
        <nav class="dash-nav" aria-label="Dashboard">
            {{-- Phones and tablets: a "Menu" button (a checkbox, so it also works without JavaScript) opens the whole list. --}}
            <input type="checkbox" id="dash-menu-open" class="dash-nav__check" aria-label="Show or hide the menu">
            <label for="dash-menu-open" class="dash-nav__toggle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Menu</span>
                <strong>{{ collect($nav)->firstWhere('active', true)['label'] ?? '' }}</strong>
            </label>
            <ul>
                @foreach ($nav as $item)
                    <li>
                        <a href="{{ $item['href'] ?? '#' }}" @class(['dash-nav__link', 'is-active' => $item['active'] ?? false, 'is-soon' => $item['soon'] ?? false]) @if ($item['active'] ?? false) aria-current="page" @endif @if ($item['soon'] ?? false) aria-disabled="true" @endif>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            {{ $item['label'] }}
                            @if ($item['soon'] ?? false)<span class="dash-nav__soon">soon</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <main class="dash-main">
            @if (session('notice'))
                <div class="form-note" role="status">{{ session('notice') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
