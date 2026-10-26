{{--
    Fonts come from our own server (no Google request, no waiting for another site). Only the two most used faces are
    preloaded: body text (Roboto 400) and headings (Roboto 700). The @font-face rules are in app.css.
    If a right-to-left Urdu page is ever added, self-host an Urdu font too (the page rules only allow fonts from 'self').
--}}
<link rel="preload" href="{{ Vite::asset('resources/fonts/roboto-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ Vite::asset('resources/fonts/roboto-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
