@extends('layouts.auth')

@section('title', 'Verify your phone | ' . config('app.name'))
@section('auth-title', 'Enter your code')
@section('auth-lead', 'We sent a 6-digit code to your phone by SMS or WhatsApp.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('verify-phone.check') }}">
    @csrf
    <div class="field">
        <label for="code">6-digit code</label>
        <input id="code" name="code" class="input otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" placeholder="••••••">
    </div>
    <div class="otp-meta">
        <span>Didn't get it? You can resend in 0:59</span>
        <a href="{{ route('register') }}">Change number</a>
    </div>
    <button type="submit" class="btn btn-primary">Verify</button>
</form>
@endsection
