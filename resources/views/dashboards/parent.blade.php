@extends('layouts.dashboard')

@section('title', 'Parent dashboard')

@php
    $children = [['Ahmed', 'FSc Part 1'], ['Sara', 'Grade 6']];
    $upcoming = [
        ['day' => '26', 'month' => 'Oct', 'who' => 'Ahmed', 'subject' => 'Physics', 'tutor' => 'Ayesha R.', 'time' => 'Sat, 10:00 AM'],
        ['day' => '27', 'month' => 'Oct', 'who' => 'Sara', 'subject' => 'English', 'tutor' => 'Nadia A.', 'time' => 'Sun, 11:00 AM'],
        ['day' => '28', 'month' => 'Oct', 'who' => 'Ahmed', 'subject' => 'Mathematics', 'tutor' => 'Bilal K.', 'time' => 'Mon, 6:00 PM'],
    ];
    $attendance = [
        ['date' => '23 Oct', 'who' => 'Ahmed', 'class' => 'Physics', 'status' => ['Attended', 'green'], 'mins' => '60 min'],
        ['date' => '22 Oct', 'who' => 'Sara', 'class' => 'English', 'status' => ['Attended', 'green'], 'mins' => '55 min'],
        ['date' => '21 Oct', 'who' => 'Ahmed', 'class' => 'Mathematics', 'status' => ['Late 10 min', 'amber'], 'mins' => '50 min'],
        ['date' => '19 Oct', 'who' => 'Sara', 'class' => 'English', 'status' => ['Missed', 'red'], 'mins' => '—'],
    ];
    $payments = [
        ['what' => 'Ahmed · Physics package (8 classes)', 'date' => '1 Oct', 'amount' => 'PKR 12,960', 'status' => ['Paid', 'green']],
        ['what' => 'Sara · English package (8 classes)', 'date' => '1 Oct', 'amount' => 'PKR 9,360', 'status' => ['Paid', 'green']],
        ['what' => 'Ahmed · Maths, 4 sessions', 'date' => '24 Oct', 'amount' => 'PKR 4,800', 'status' => ['Awaiting payment', 'amber']],
    ];
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Good evening, Sadia</h1>
        <p>Your children's classes and progress in one place.</p>
    </div>
    <div class="child-tabs" role="group" aria-label="Show child">
        <button type="button" class="btn btn-primary btn-sm">All children</button>
        @foreach ($children as [$name, $grade])
            <button type="button" class="btn btn-outline btn-sm">{{ $name }} · {{ $grade }}</button>
        @endforeach
    </div>
</div>

<div class="stat-grid">
    <div class="stat"><span class="stat__label">Attendance this month</span><span class="stat__value">92%</span><span class="stat__hint">11 of 12 classes</span></div>
    <div class="stat"><span class="stat__label">Classes this month</span><span class="stat__value">12</span><span class="stat__hint">5 more booked</span></div>
    <div class="stat"><span class="stat__label">Spent this month</span><span class="stat__value">PKR 27,120</span><span class="stat__hint">2 packages, 4 sessions</span></div>
    <div class="stat"><span class="stat__label">To pay</span><span class="stat__value">PKR 4,800</span><span class="stat__hint stat__hint--warn">due before 28 Oct</span></div>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Upcoming classes</h2><a href="#">All classes</a></div>
        <ul class="item-list">
            @foreach ($upcoming as $c)
                <li>
                    <span class="date-chip"><strong>{{ $c['day'] }}</strong><span>{{ $c['month'] }}</span></span>
                    <div class="item-list__main"><strong>{{ $c['who'] }} · {{ $c['subject'] }}</strong><span>{{ $c['tutor'] }} · {{ $c['time'] }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Latest tutor feedback</h2><a href="#">All feedback</a></div>
        <div class="note-card">
            <p>"Ahmed solved all projectile questions on his own today. He should practise exam timing next."</p>
            <span>Ayesha R. · Physics · 23 Oct</span>
        </div>
        <div class="note-card">
            <p>"Sara's reading is more confident. We started paragraph writing; she needs to practise spelling."</p>
            <span>Nadia A. · English · 22 Oct</span>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel__head"><h2>Attendance</h2><a href="#">Full report</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Date</th><th scope="col">Child</th><th scope="col">Class</th><th scope="col">Time in class</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @foreach ($attendance as $a)
                    <tr>
                        <td>{{ $a['date'] }}</td><td>{{ $a['who'] }}</td><td>{{ $a['class'] }}</td><td>{{ $a['mins'] }}</td>
                        <td><span class="status status--{{ $a['status'][1] }}">{{ $a['status'][0] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

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
        <div><button type="button" class="btn btn-primary btn-sm">Pay PKR 4,800</button></div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Settings for your children</h2></div>
        <label class="check"><input type="checkbox" checked disabled> Record classes for revision (parent consent)</label>
        <label class="check"><input type="checkbox" checked disabled> WhatsApp reminder 1 hour before each class</label>
        <label class="check"><input type="checkbox" disabled> Email a weekly progress summary</label>
    </section>
</div>
@endsection
