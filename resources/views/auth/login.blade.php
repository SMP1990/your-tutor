@extends('layouts.auth')

@section('title', 'Log in | ' . config('app.name'))
@section('auth-title', 'Welcome back')
@section('auth-lead', 'Log in to see your classes, bookings and payments.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('login.attempt') }}">
    @csrf
    <div class="field">
        <label for="login">Email or phone number</label>
        <input id="login" name="login" class="input" required autocomplete="username" placeholder="you@example.com or 0300 1234567">
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
    </div>
    <div class="auth-row">
        <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary">Log in</button>
</form>

<div class="auth-divider">or</div>

<a href="{{ route('verify-phone') }}" class="btn btn-outline">Log in with a code on your phone</a>

<p class="auth-switch">New here? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
