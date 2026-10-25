@extends('layouts.dashboard')

@section('title', 'Site settings')

@section('content')
<div class="dash-head">
    <div>
        <h1>Site settings</h1>
        <p>The Home page picture, contact details and the four numbers on the Home page. A box left empty uses the default from the server settings. The brand name ({{ config('app.name') }}) is changed in the server's .env file, not here.</p>
    </div>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

{{-- Home page picture: its own forms (a picture needs a different kind of form than the settings below). --}}
@php $heroErrors = $errors->getBag('hero'); @endphp
<section class="panel" id="hero-picture">
    <div class="panel__head"><h2>Home page picture</h2></div>
    @if ($heroErrors->any())<div class="form-alert" role="alert">{{ $heroErrors->first() }}</div>@endif

    @if ($hero)
        <img src="{{ $hero['small'] }}" alt="{{ $hero['alt'] }}" width="{{ $hero['small_width'] }}" height="{{ $hero['small_height'] }}" class="settings-preview">
        <p class="muted">This picture is on the Home page, next to the search box. The site found that it is {{ $hero['mode'] === 'cutout' ? 'a cut-out with a transparent background, so it stands on the coloured circles' : 'an ordinary photo, so it is shown in a white frame' }}. (The chequered pattern above only shows transparent areas.)</p>

        <form method="post" action="{{ route('admin.hero.update') }}" class="demo-form" novalidate>
            @csrf
            @method('PUT')
            <div class="field">
                <label for="hero_alt">Short description <span class="field__optional">(for people who cannot see the picture, and for search engines)</span></label>
                <input id="hero_alt" name="alt" class="input" maxlength="{{ \App\Support\HeroPicture::ALT_MAX }}" value="{{ old('alt', $hero['alt']) }}" placeholder="e.g. A student learning online with her tutor">
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-outline btn-sm">Save description</button></div>
        </form>
    @else
        <p class="muted">No picture yet: the Home page shows its default drawing (a sample tutor card). Add a picture to replace it.</p>
    @endif

    <form method="post" action="{{ route('admin.hero.store') }}" enctype="multipart/form-data" class="demo-form" novalidate>
        @csrf
        <div class="field">
            <label for="hero_picture">{{ $hero ? 'Replace the picture' : 'Add a picture' }}</label>
            <input id="hero_picture" name="picture" type="file" accept="image/*,.heic,.heif" class="input" required>
            <p class="field__hint">Any picture works, of any size (photos straight from your phone are fine). The site makes it the right size and converts it to a fast format by itself, and it never cuts the picture. A person on a plain background is cut out automatically so she stands on the coloured circles (a PNG that is already transparent works best of all).</p>
        </div>
        <div class="field">
            <label for="hero_background">Background of the picture</label>
            <select id="hero_background" name="background" class="input">
                <option value="auto" @selected(old('background', 'auto') === 'auto')>Automatic (recommended): take away a plain white, grey or chequered background</option>
                <option value="keep" @selected(old('background') === 'keep')>Keep the picture exactly as it is</option>
                <option value="remove" @selected(old('background') === 'remove')>Try to take away any plain background (any flat colour)</option>
            </select>
            <p class="field__hint">When the background is taken away, the person stands on the coloured circles. It works for plain backgrounds; a busy background (a room, a street) is left as it is.</p>
        </div>
        @unless ($hero)
            <div class="field">
                <label for="hero_new_alt">Short description <span class="field__optional">(optional)</span></label>
                <input id="hero_new_alt" name="alt" class="input" maxlength="{{ \App\Support\HeroPicture::ALT_MAX }}" value="{{ old('alt') }}">
            </div>
        @endunless
        <div class="form-actions"><button type="submit" class="btn btn-primary btn-sm">{{ $hero ? 'Upload new picture' : 'Upload picture' }}</button></div>
    </form>

    @if ($hero)
        <form method="post" action="{{ route('admin.hero.destroy') }}" data-confirm="Remove the picture? The Home page goes back to its default drawing." class="demo-form">
            @csrf
            @method('DELETE')
            <div class="form-actions"><button type="submit" class="btn btn-outline btn-danger btn-sm">Remove picture</button></div>
        </form>
    @endif
</section>

<form method="post" action="{{ route('admin.settings.update') }}" class="demo-form" novalidate>
    @csrf
    @method('PUT')

    <section class="panel">
        <div class="panel__head"><h2>Contact details</h2></div>
        <div class="field">
            <label for="whatsapp">WhatsApp number</label>
            <input id="whatsapp" name="whatsapp" class="input @error('whatsapp') is-invalid @enderror" maxlength="25" value="{{ old('whatsapp', $whatsapp) }}" placeholder="+92 300 1234567" inputmode="tel">
            <p class="field__hint">With country code. Shown on the site and used for the WhatsApp buttons.</p>
        </div>
        <div class="field">
            <label for="support_email">Support email</label>
            <input id="support_email" name="support_email" type="email" class="input @error('support_email') is-invalid @enderror" maxlength="120" value="{{ old('support_email', $support_email) }}" placeholder="{{ config('site.support_email') }}">
        </div>
        <div class="field">
            <label for="support_hours">Support hours</label>
            <input id="support_hours" name="support_hours" class="input @error('support_hours') is-invalid @enderror" maxlength="100" value="{{ old('support_hours', $support_hours) }}" placeholder="Monday to Saturday, 10 am to 8 pm (PKT)">
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Home page numbers</h2></div>
        <p class="muted">Type your own number, or let the site count it from real data. A number that counts as zero is hidden from the Home page. Until you have real figures, keep "Type my own" and use honest numbers.</p>
        @foreach ($stats as $stat => $s)
            @php $mode = old("stats.{$stat}.mode", $s['mode']); @endphp
            <fieldset class="form-section">
                <legend>{{ $s['label'] }}</legend>
                <div class="pills">
                    <label class="pill"><input type="radio" name="stats[{{ $stat }}][mode]" value="manual" @checked($mode === 'manual')><span>Type my own</span></label>
                    <label class="pill"><input type="radio" name="stats[{{ $stat }}][mode]" value="auto" @checked($mode === 'auto')><span>Count automatically</span></label>
                </div>
                <div class="field">
                    <label for="stat-{{ $stat }}">Number to show when typed</label>
                    <input id="stat-{{ $stat }}" name="stats[{{ $stat }}][value]" class="input @error("stats.{$stat}.value") is-invalid @enderror" maxlength="12" value="{{ old("stats.{$stat}.value", $s['value']) }}">
                    @error("stats.{$stat}.value") <p class="field__error">{{ $message }}</p> @enderror
                    <p class="field__hint">Counting automatically would show right now: <strong>{{ $s['auto'] ?? 'nothing (hidden)' }}</strong></p>
                </div>
            </fieldset>
        @endforeach
    </section>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
</form>
@endsection
