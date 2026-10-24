@extends('layouts.dashboard')

@section('title', 'My dashboard')

@section('content')
<div class="dash-head">
    <div>
        <h1>Hi {{ strtok($account->name, ' ') }}</h1>
        <p>Welcome to your dashboard.</p>
    </div>
    <a href="{{ route('tutors.index') }}" class="btn btn-primary btn-sm">Find a tutor</a>
</div>

<div class="dash-grid dash-grid--halves">
    @include('dashboard.partials.demo-requests', ['demos' => $demos])
    @include('dashboard.partials.account')
</div>
@endsection
