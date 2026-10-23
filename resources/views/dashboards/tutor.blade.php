@extends('layouts.dashboard')

@section('title', 'Tutor dashboard')

@php
    $today = [
        ['time' => '5:00 PM', 'student' => 'Ahmed M.', 'what' => 'Physics · FSc', 'kind' => ['Regular', 'grey']],
        ['time' => '6:30 PM', 'student' => 'Hira S.', 'what' => 'Mathematics · O Level', 'kind' => ['Free demo', 'amber']],
        ['time' => '8:00 PM', 'student' => 'Usman T.', 'what' => 'Physics · Matric', 'kind' => ['Regular', 'grey']],
    ];
    $requests = [
        ['name' => 'Rabia (parent)', 'what' => 'Physics · FSc Part 2', 'when' => 'Sat morning'],
        ['name' => 'Kamran (student)', 'what' => 'Mathematics · O Level', 'when' => 'Wed evening'],
    ];
    $checklist = [
        ['Profile photo', true], ['Intro video', false], ['Qualifications', true], ['Availability', true], ['Rates', true],
    ];
    $done = count(array_filter($checklist, fn ($c) => $c[1]));
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Welcome, Ayesha</h1>
        <p><span class="status status--green">Profile live</span> Students can find and book you.</p>
    </div>
    <a href="{{ route('tutors.show', 'ayesha-r') }}" class="btn btn-outline btn-sm">View my public profile</a>
</div>

<div class="stat-grid">
    <div class="stat"><span class="stat__label">Available to withdraw</span><span class="stat__value">PKR 21,600</span><span class="stat__hint">from 12 completed classes</span></div>
    <div class="stat"><span class="stat__label">Held until class is done</span><span class="stat__value">PKR 9,000</span><span class="stat__hint">5 upcoming paid classes</span></div>
    <div class="stat"><span class="stat__label">Earned this month</span><span class="stat__value">PKR 48,600</span><span class="stat__hint">before platform fee</span></div>
    <div class="stat"><span class="stat__label">Rating</span><span class="stat__value">4.9 ★</span><span class="stat__hint">38 reviews</span></div>
</div>

<div class="dash-grid dash-grid--2">
    <section class="panel">
        <div class="panel__head"><h2>Today's classes</h2><a href="#">Full timetable</a></div>
        <ul class="item-list">
            @foreach ($today as $c)
                <li>
                    <span class="date-chip"><strong>{{ explode(' ', $c['time'])[0] }}</strong><span>{{ explode(' ', $c['time'])[1] }}</span></span>
                    <div class="item-list__main"><strong>{{ $c['student'] }}</strong><span>{{ $c['what'] }}</span></div>
                    <div class="item-list__side">
                        <span class="status status--{{ $c['kind'][1] }}">{{ $c['kind'][0] }}</span>
                        <button type="button" class="btn btn-primary btn-sm" disabled>Start</button>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Withdraw earnings</h2></div>
        <p class="muted">Send your available balance to your account. Usually arrives within 2 working days.</p>
        <div class="field">
            <label for="payout">Pay to</label>
            <select id="payout" class="input" disabled>
                @foreach (config('pricing.payout_methods') as $method)
                    <option>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div><button type="button" class="btn btn-primary btn-sm" disabled>Withdraw PKR 21,600</button></div>
    </section>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>New demo requests</h2><span class="status status--amber">{{ count($requests) }} new</span></div>
        <ul class="item-list">
            @foreach ($requests as $r)
                <li>
                    <div class="item-list__main"><strong>{{ $r['name'] }}</strong><span>{{ $r['what'] }} · prefers {{ $r['when'] }}</span></div>
                    <div class="item-list__side">
                        <button type="button" class="btn btn-primary btn-sm">Accept</button>
                        <button type="button" class="btn btn-outline btn-sm">Decline</button>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Profile strength</h2><strong>{{ $done }}/{{ count($checklist) }}</strong></div>
        <div class="progress"><progress value="{{ $done }}" max="{{ count($checklist) }}">{{ $done }} of {{ count($checklist) }}</progress></div>
        <ul class="item-list">
            @foreach ($checklist as [$label, $ok])
                <li>
                    <div class="item-list__main"><strong>{{ $label }}</strong></div>
                    <div class="item-list__side">
                        @if ($ok)
                            <span class="status status--green">Done</span>
                        @else
                            <button type="button" class="btn btn-outline btn-sm">Add now</button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
