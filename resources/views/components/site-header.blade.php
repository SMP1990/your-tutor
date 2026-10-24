@php
    $links = [
        ['label' => 'Find a Tutor', 'href' => url('/tutors')],
        ['label' => 'Subjects', 'href' => route('subjects.index')],
        ['label' => 'Pricing', 'href' => route('pricing')],
        ['label' => 'How it works', 'href' => route('how-it-works')],
        ['label' => 'Become a Tutor', 'href' => route('become-a-tutor')],
    ];
@endphp

<header class="site-header">
    <div class="container site-header__bar">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand__mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 5h7a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H2zM22 5h-7a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h8z"/></svg>
            </span>
            <strong class="brand__name">{{ config('app.name') }}</strong>
        </a>

        <nav class="site-nav" aria-label="Main">
            <ul class="site-nav__list">
                @foreach ($links as $link)
                    <li><a class="nav-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="site-header__actions">
            @auth
                <span class="site-header__login site-header__hello">Hi, {{ strtok(auth()->user()->name, ' ') }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline site-header__register">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link site-header__login">Log In</a>
                <a href="{{ route('register') }}" class="btn btn-primary site-header__register">Register</a>
            @endauth
            <button type="button" class="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav" data-menu-toggle>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile" hidden data-mobile-nav>
        <ul class="container mobile-nav__list">
            @foreach ($links as $link)
                <li><a class="nav-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
            @endforeach
            @guest
                <li><a class="nav-link" href="{{ route('login') }}"><strong>Log In</strong></a></li>
            @endguest
        </ul>
    </nav>
</header>

