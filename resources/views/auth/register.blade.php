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
    $selected = array_key_exists(request('role'), $roles) ? request('role') : 'parent';
@endphp

@section('auth-body')
<form class="auth-form" method="post" action="{{ route('register.store') }}">
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

    <div class="field">
        <label for="name">Full name</label>
        <input id="name" name="name" class="input" required autocomplete="name">
    </div>
    <div class="field">
        <label for="phone">WhatsApp number</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" class="input" required autocomplete="tel" placeholder="0300 1234567">
    </div>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input" required autocomplete="email">
    </div>
    <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input" required minlength="8" autocomplete="new-password">
    </div>

    <label class="check">
        <input type="checkbox" name="whatsapp_ok" value="1" checked>
        Send me class reminders and updates on WhatsApp.
    </label>
    <label class="check">
        <input type="checkbox" name="terms" value="1" required>
        <span>I agree to the <a href="{{ route('terms') }}">Terms</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</span>
    </label>

    <button type="submit" class="btn btn-primary">Create account</button>
</form>

<p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection

@push('scripts')
<script>
    // Pre-select the role from ?role=tutor (also works on the static preview).
    (function () {
        var role = new URLSearchParams(location.search).get('role');
        var input = role && document.querySelector('[data-role][value="' + role + '"]');
        if (input) input.checked = true;
    })();
</script>
@endpush
