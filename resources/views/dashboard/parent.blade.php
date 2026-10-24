@extends('layouts.dashboard')

@section('title', 'Parent dashboard')

@section('content')
<div class="dash-head">
    <div>
        <h1>Hi {{ strtok($account->name, ' ') }}</h1>
        <p>Your children's classes and progress will appear here.</p>
    </div>
    <a href="{{ route('tutors.index') }}" class="btn btn-primary btn-sm">Find a tutor</a>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>My children</h2><span class="status status--grey">{{ $children->count() }}</span></div>
        @if ($children->isEmpty())
            <p class="muted">No children added yet. Adding children comes in the next update.</p>
        @else
            <ul class="item-list">
                @foreach ($children as $child)
                    <li>
                        <span class="avatar" aria-hidden="true">{{ mb_substr($child->name, 0, 1) }}</span>
                        <div class="item-list__main"><strong>{{ $child->name }}</strong><span>{{ $child->grade ?: 'Grade not set' }}</span></div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @include('dashboard.partials.coming-soon', [
        'title' => 'Classes and attendance',
        'text' => 'Upcoming classes, attendance and tutor feedback for each child will show here once booking opens.',
    ])
</div>

@include('dashboard.partials.account')
@endsection
