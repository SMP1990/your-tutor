@extends('layouts.auth')

@section('title', 'Set a new password | ' . config('app.name'))
@section('auth-title', 'Set a new password')
@section('auth-lead', 'Choose a password you have not used here before.')

@section('auth-body')
@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<form class="auth-form" method="post" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input" value="{{ old('email', $email) }}" required autocomplete="email">
    </div>
    <div class="field">
        <label for="password">New password</label>
        <input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password" aria-describedby="password-hint">
        <p id="password-hint" class="field__hint">At least 8 characters, with letters and numbers.</p>
    </div>
    <div class="field">
        <label for="password_confirmation">Type it again</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Save new password</button>
</form>
@endsection
