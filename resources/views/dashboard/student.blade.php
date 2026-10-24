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
    @include('dashboard.partials.coming-soon', [
        'title' => 'My classes',
        'text' => 'You have no classes yet. Once booking opens, your free demo and regular classes will show here.',
        'action' => ['href' => route('tutors.index'), 'label' => 'Browse tutors'],
    ])
    @include('dashboard.partials.account')
</div>
@endsection
