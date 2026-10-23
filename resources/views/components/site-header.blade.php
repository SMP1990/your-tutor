@php
    $links = [
        ['label' => 'Find a Tutor', 'href' => url('/tutors')],
        ['label' => 'Subjects', 'href' => url('/#subjects')],
        ['label' => 'Pricing', 'href' => '#'],
        ['label' => 'How it works', 'href' => url('/#how-it-works')],
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
            <a href="#" class="nav-link site-header__login">Log In</a>
            <a href="#" class="btn btn-primary site-header__register">Register</a>
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
            <li><a class="nav-link" href="#"><strong>Log In</strong></a></li>
        </ul>
    </nav>
</header>

@once
    @push('scripts')
        <script>
            (function () {
                var btn = document.querySelector('[data-menu-toggle]');
                var nav = document.querySelector('[data-mobile-nav]');
                if (!btn || !nav) return;
                btn.addEventListener('click', function () {
                    var opening = nav.hasAttribute('hidden');
                    nav.toggleAttribute('hidden', !opening);
                    btn.setAttribute('aria-expanded', opening ? 'true' : 'false');
                    btn.setAttribute('aria-label', opening ? 'Close menu' : 'Open menu');
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !nav.hasAttribute('hidden')) {
                        btn.click();
                        btn.focus();
                    }
                });
            })();
        </script>
    @endpush
@endonce
