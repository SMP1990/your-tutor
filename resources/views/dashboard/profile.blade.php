@extends('layouts.dashboard')

@section('title', 'Profile')

@php
    $details = $errors->getBag('details');
    $emailBag = $errors->getBag('email');
    $passwordBag = $errors->getBag('password');
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Your profile</h1>
        <p>Change your details here. We ask for your current password each time, to keep your account safe.</p>
    </div>
</div>

{{-- 1. Name and phone --}}
<form method="post" action="{{ route('profile.details') }}" class="demo-form panel" novalidate>
    @csrf
    @method('PUT')
    <div class="panel__head"><h2>Name and phone</h2></div>
    @if ($details->any())<div class="form-alert" role="alert">{{ $details->first() }}</div>@endif

    <div class="field">
        <label for="name">Full name</label>
        <input id="name" name="name" class="input @if ($details->has('name')) is-invalid @endif" maxlength="80" value="{{ old('name', $account->name) }}" autocomplete="name" @disabled($nameLocked) required>
        @if ($nameLocked)<p class="field__hint">Your name was checked against your ID and shows on your public profile. To change it, message us from the Contact page.</p>@endif
    </div>
    <div class="field">
        <label for="phone">WhatsApp / mobile number</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" class="input @if ($details->has('phone')) is-invalid @endif" value="{{ old('phone', $account->phone) }}" placeholder="0300 1234567" autocomplete="tel" required>
    </div>
    <div class="field">
        <label for="details_password">Current password</label>
        <input id="details_password" name="current_password" type="password" class="input @if ($details->has('current_password')) is-invalid @endif" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary">Save details</button>
</form>

{{-- 2. Email --}}
<form method="post" action="{{ route('profile.email') }}" class="demo-form panel" novalidate>
    @csrf
    <div class="panel__head"><h2>Email</h2></div>
    @if ($emailBag->any())<div class="form-alert" role="alert">{{ $emailBag->first() }}</div>@endif

    <p>Your email now: <strong>{{ $account->email }}</strong></p>
    @if ($account->pending_email)
        <p class="field__hint">Waiting for you to confirm <strong>{{ $account->pending_email }}</strong>: open the link we sent there. You can ask for a new link below.</p>
    @endif
    <div class="field">
        <label for="email">New email</label>
        <input id="email" name="email" type="email" class="input @if ($emailBag->has('email')) is-invalid @endif" maxlength="120" value="{{ old('email') }}" autocomplete="email" required>
        <p class="field__hint">We send a link to the new address. Your email only changes after you open it.</p>
    </div>
    <div class="field">
        <label for="email_password">Current password</label>
        <input id="email_password" name="current_password" type="password" class="input @if ($emailBag->has('current_password')) is-invalid @endif" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary">Send confirmation link</button>
</form>

{{-- 3. Password --}}
<form method="post" action="{{ route('profile.password') }}" class="demo-form panel" novalidate>
    @csrf
    @method('PUT')
    <div class="panel__head"><h2>Password</h2></div>
    @if ($passwordBag->any())<div class="form-alert" role="alert">{{ $passwordBag->first() }}</div>@endif

    <div class="field">
        <label for="password_current">Current password</label>
        <input id="password_current" name="current_password" type="password" class="input @if ($passwordBag->has('current_password')) is-invalid @endif" autocomplete="current-password" required>
    </div>
    <div class="field">
        <label for="password">New password</label>
        <input id="password" name="password" type="password" class="input @if ($passwordBag->has('password')) is-invalid @endif" maxlength="72" autocomplete="new-password" required>
        <p class="field__hint">At least {{ $passwordMin }} characters, with letters and numbers.</p>
    </div>
    <div class="field">
        <label for="password_confirmation">New password again</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="input" maxlength="72" autocomplete="new-password" required>
    </div>
    <button type="submit" class="btn btn-primary">Change password</button>
</form>
@endsection
