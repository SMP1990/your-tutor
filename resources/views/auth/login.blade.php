@extends('layouts.auth')

@section('title', 'Log in | ' . config('app.name'))
@section('auth-title', 'Welcome back')
@section('auth-lead', 'Log in to see your classes, bookings and payments.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('login.attempt') }}" novalidate>
    @csrf
    <div class="field">
        <label for="login">Email or phone number</label>
        <input id="login" name="login" class="input @error('login') is-invalid @enderror" value="{{ old('login') }}" required autocomplete="username" placeholder="you@example.com or 0300 1234567">
        @error('login') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" required autocomplete="current-password">
        @error('password') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <div class="auth-row">
        <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary">Log in</button>
</form>

<p class="auth-switch">New here? <a href="{{ route('register') }}">Create an account</a></p>

@include('partials.dashboard-preview-links')
@endsection
