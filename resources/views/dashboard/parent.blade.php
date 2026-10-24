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
        <div class="panel__head">
            <h2>My children</h2>
            <a href="{{ route('children.create') }}" class="btn btn-primary btn-sm">+ Add child</a>
        </div>
        @if ($children->isEmpty())
            <p class="muted">No children added yet. Add your child to book classes for them.</p>
        @else
            <ul class="item-list">
                @foreach ($children as $child)
                    <li>
                        <span class="avatar" aria-hidden="true">{{ mb_substr($child->name, 0, 1) }}</span>
                        <div class="item-list__main"><strong>{{ $child->name }}</strong><span>{{ $child->grade ?: 'Grade not set' }}</span></div>
                        <div class="item-list__side">
                            <a href="{{ route('children.edit', $child) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="{{ route('children.destroy', $child) }}" data-confirm="Remove {{ $child->name }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm btn-danger">Remove</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @include('dashboard.partials.next-classes', ['nextClasses' => $nextClasses, 'isTutor' => false])
    @include('dashboard.partials.demo-requests', ['demos' => $demos, 'bookedPairs' => $bookedPairs])
    @include('dashboard.partials.class-bookings', ['bookings' => $bookings])
</div>

@include('dashboard.partials.account')
@endsection
