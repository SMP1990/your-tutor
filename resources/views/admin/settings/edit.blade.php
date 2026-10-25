@extends('layouts.dashboard')

@section('title', 'Site settings')

@section('content')
<div class="dash-head">
    <div>
        <h1>Site settings</h1>
        <p>Contact details and the four numbers on the Home page. A box left empty uses the default from the server settings. The brand name ({{ config('app.name') }}) is changed in the server's .env file, not here.</p>
    </div>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

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
