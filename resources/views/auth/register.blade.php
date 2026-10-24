@extends('layouts.auth')

@section('title', 'Create an account | ' . config('app.name'))
@section('auth-title', 'Create your account')
@section('auth-lead', 'It takes one minute. Your first demo class is free.')

@php
    $roles = [
        'parent' => ['Parent', 'For my child'],
        'student' => ['Student', 'For myself'],
        'tutor' => ['Tutor', 'I want to teach'],
    ];
    $selected = old('role', array_key_exists(request('role'), $roles) ? request('role') : 'parent');
@endphp

@section('auth-body')
@if ($errors->any())
    <div class="form-alert" role="alert">Please check the highlighted fields.</div>
@endif

<form class="auth-form" method="post" action="{{ route('register.store') }}" novalidate>
    @csrf
    <fieldset class="role-picker">
        <legend>I am a…</legend>
        @foreach ($roles as $value => [$label, $hint])
            <label class="role">
                <input type="radio" name="role" value="{{ $value }}" @checked($selected === $value) data-role>
                <span>{{ $label }}<small>{{ $hint }}</small></span>
            </label>
        @endforeach
    </fieldset>
    @error('role') <p class="field__error">{{ $message }}</p> @enderror

    <div class="field">
        <label for="name">Full name</label>
        <input id="name" name="name" class="input @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name">
        @error('name') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="phone">WhatsApp number</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" class="input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required autocomplete="tel" placeholder="0300 1234567">
        @error('phone') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email">
        @error('email') <p class="field__error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password" aria-describedby="password-hint">
        <p id="password-hint" class="field__hint">At least 8 characters, with letters and numbers.</p>
        @error('password') <p class="field__error">{{ $message }}</p> @enderror
    </div>

    <label class="check">
        <input type="checkbox" name="whatsapp_ok" value="1" @checked(old('whatsapp_ok', ! $errors->any()))>
        Send me class reminders and updates on WhatsApp.
    </label>
    <label class="check">
        <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
        <span>I agree to the <a href="{{ route('terms') }}">Terms</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</span>
    </label>
    @error('terms') <p class="field__error">{{ $message }}</p> @enderror

    <button type="submit" class="btn btn-primary">Create account</button>
</form>

<p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
