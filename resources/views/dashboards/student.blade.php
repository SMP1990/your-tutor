@extends('layouts.dashboard')

@section('title', 'Student dashboard')

@php
    $upcoming = [
        ['day' => '26', 'month' => 'Oct', 'subject' => 'Physics', 'tutor' => 'Ayesha R.', 'time' => 'Sat, 10:00 AM · 60 min'],
        ['day' => '28', 'month' => 'Oct', 'subject' => 'Mathematics', 'tutor' => 'Bilal K.', 'time' => 'Mon, 6:00 PM · 60 min'],
        ['day' => '30', 'month' => 'Oct', 'subject' => 'Physics', 'tutor' => 'Ayesha R.', 'time' => 'Wed, 5:00 PM · 60 min'],
    ];
    $notes = [
        ['subject' => 'Physics', 'date' => '23 Oct', 'text' => 'Covered projectile motion. Practise Q4–Q10 from chapter 3 before Saturday.'],
        ['subject' => 'Mathematics', 'date' => '21 Oct', 'text' => 'Quadratic equations: completing the square. Good progress, revise the formula method.'],
    ];
    $payments = [
        ['what' => 'Physics · 8-class package', 'date' => '1 Oct', 'amount' => 'PKR 12,960', 'status' => ['Paid', 'green']],
        ['what' => 'Mathematics · 4 sessions', 'date' => '3 Oct', 'amount' => 'PKR 4,800', 'status' => ['Paid', 'green']],
    ];
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Hi Ahmed 👋</h1>
        <p>Here is your week at a glance.</p>
    </div>
    <a href="{{ route('tutors.index') }}" class="btn btn-outline btn-sm">Find another tutor</a>
</div>

<div class="dash-grid dash-grid--2">
    <section class="panel panel--accent next-class" aria-labelledby="next-title">
        <h2 id="next-title" class="muted">Next class</h2>
        <div>
            <div class="next-class__time">Sat 26 Oct, 10:00 AM</div>
            <p>Physics with Ayesha R. · 60 minutes</p>
        </div>
        <div class="next-class__actions">
            <button type="button" class="btn btn-light btn-sm" disabled>Join class</button>
            <span class="muted">Opens 10 minutes before the class</span>
        </div>
    </section>

    <div class="stat-grid stat-grid--2">
        <div class="stat"><span class="stat__label">Classes this month</span><span class="stat__value">6</span><span class="stat__hint">2 more booked</span></div>
        <div class="stat"><span class="stat__label">Hours learnt</span><span class="stat__value">14</span><span class="stat__hint">since September</span></div>
        <div class="stat"><span class="stat__label">Practice to do</span><span class="stat__value">2</span><span class="stat__hint stat__hint--warn">due Saturday</span></div>
        <div class="stat"><span class="stat__label">Classes left</span><span class="stat__value">3</span><span class="stat__hint">in Physics package</span></div>
    </div>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Upcoming classes</h2><a href="#">All classes</a></div>
        <ul class="item-list">
            @foreach ($upcoming as $c)
                <li>
                    <span class="date-chip"><strong>{{ $c['day'] }}</strong><span>{{ $c['month'] }}</span></span>
                    <div class="item-list__main"><strong>{{ $c['subject'] }} · {{ $c['tutor'] }}</strong><span>{{ $c['time'] }}</span></div>
                    <div class="item-list__side"><button type="button" class="btn btn-outline btn-sm">Reschedule</button></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Latest class notes</h2><a href="#">All notes</a></div>
        @foreach ($notes as $n)
            <div class="note-card">
                <p>{{ $n['text'] }}</p>
                <span>{{ $n['subject'] }} · {{ $n['date'] }}</span>
            </div>
        @endforeach
    </section>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Payments</h2><a href="#">All payments</a></div>
        <ul class="item-list">
            @foreach ($payments as $p)
                <li>
                    <div class="item-list__main"><strong>{{ $p['what'] }}</strong><span>{{ $p['date'] }}</span></div>
                    <div class="item-list__side"><strong>{{ $p['amount'] }}</strong><span class="status status--{{ $p['status'][1] }}">{{ $p['status'][0] }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>How was your class?</h2></div>
        <p class="muted">You had 3 classes with Bilal K. Share a quick review to help other students.</p>
        <div><button type="button" class="btn btn-primary btn-sm">Write a review</button></div>
    </section>
</div>
@endsection
