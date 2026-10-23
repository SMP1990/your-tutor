@extends('layouts.auth')

@section('title', 'Forgot password | ' . config('app.name'))
@section('auth-title', 'Forgot your password?')
@section('auth-lead', 'Enter your email or phone number and we will send you a link or code to set a new password.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('password.email') }}">
    @csrf
    <div class="field">
        <label for="login">Email or phone number</label>
        <input id="login" name="login" class="input" required autocomplete="username">
    </div>
    <button type="submit" class="btn btn-primary">Send reset link</button>
</form>

<p class="auth-switch">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
@endsection
