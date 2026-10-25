{{--
    Fonts come from our own server (no Google request, no waiting for another site). Only the two most used faces are
    preloaded: body text (DM Sans 400) and headings (Bricolage Grotesque 700). The @font-face rules are in app.css.
    If a right-to-left Urdu page is ever added, self-host an Urdu font too (the page rules only allow fonts from 'self').
--}}
<link rel="preload" href="{{ Vite::asset('resources/fonts/dm-sans-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ Vite::asset('resources/fonts/bricolage-grotesque-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
