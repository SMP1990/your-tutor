@extends('layouts.auth')

@section('title', 'Forgot password | ' . config('app.name'))
@section('auth-title', 'Forgot your password?')
@section('auth-lead', 'Enter the email you signed up with and we will send you a link to set a new password.')

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email">
        @error('email') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn btn-primary">Send reset link</button>
</form>

<p class="auth-switch">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
@endsection
