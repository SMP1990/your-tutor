@extends('layouts.auth')

@section('title', 'Enter your login code | ' . config('app.name'))
@section('auth-title', 'Enter your login code')
@section('auth-lead', 'For extra safety, admin accounts need a code. We sent a 6-digit code to ' . $email . '.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('two-factor.store') }}" novalidate>
    @csrf
    <div class="field">
        <label for="code">6-digit code</label>
        <input id="code" name="code" class="input otp-input @error('code') is-invalid @enderror" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" autofocus placeholder="••••••">
        @error('code') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn btn-primary">Continue</button>
</form>

<form method="post" action="{{ route('two-factor.resend') }}" class="otp-meta">
    @csrf
    <span>The code works for {{ \App\Support\TwoFactorCode::MINUTES }} minutes.</span>
    <button type="submit" class="link-button">Send a new code</button>
</form>
@endsection
