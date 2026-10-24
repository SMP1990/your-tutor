@extends('layouts.auth')

@section('title', 'Verify your email | ' . config('app.name'))

@if ($user->hasVerifiedEmail())
    @section('auth-title', 'You are all set')
    @section('auth-lead', 'Your email is verified.')
@else
    @section('auth-title', 'Check your email')
    @section('auth-lead', 'We sent a link to ' . $user->email . '. Click it to verify your email.')
@endif

@section('auth-body')
@if ($user->hasVerifiedEmail())
    <a href="{{ route('home') }}" class="btn btn-primary">Continue</a>
@else
    <ul class="auth-side__list auth-steps">
        <li>Open the email from {{ config('app.name') }}</li>
        <li>Click "Verify email address"</li>
        <li>Not there after a few minutes? Check the spam folder</li>
    </ul>

    <form method="post" action="{{ route('verification.send') }}" class="auth-form">
        @csrf
        <button type="submit" class="btn btn-outline">Send the link again</button>
    </form>
@endif
@endsection
