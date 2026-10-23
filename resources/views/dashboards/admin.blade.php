@extends('layouts.dashboard')

@section('title', 'Admin dashboard')

@php
    $applications = [
        ['name' => 'Fatima Z.', 'subjects' => 'Chemistry, Biology', 'docs' => ['Complete', 'green'], 'step' => 'Interview booked', 'date' => '23 Oct'],
        ['name' => 'Ali R.', 'subjects' => 'Mathematics', 'docs' => ['Degree missing', 'amber'], 'step' => 'Waiting for documents', 'date' => '22 Oct'],
        ['name' => 'Hina K.', 'subjects' => 'English, Urdu', 'docs' => ['Complete', 'green'], 'step' => 'Subject test passed', 'date' => '21 Oct'],
    ];
    $transfers = [
        ['who' => 'Sadia Malik', 'amount' => 'PKR 4,800', 'ref' => 'Bank transfer · Meezan', 'date' => '24 Oct'],
        ['who' => 'Imran Q.', 'amount' => 'PKR 12,960', 'ref' => 'Bank transfer · HBL', 'date' => '23 Oct'],
    ];
    $refunds = [
        ['who' => 'Nadia Ahmed', 'reason' => 'Tutor did not join (20 Oct)', 'amount' => 'PKR 1,800', 'status' => ['Open', 'amber']],
        ['who' => 'Bilal T.', 'reason' => 'Cancelled 2 days before', 'amount' => 'PKR 2,500', 'status' => ['Approved', 'green']],
    ];
    $complaints = [
        ['subject' => 'Class started 15 minutes late', 'by' => 'Parent · Hassan R.', 'status' => ['Open', 'red']],
        ['subject' => 'Video froze during class', 'by' => 'Student · Kamran', 'status' => ['In progress', 'amber']],
    ];
    $siteFigures = [
        ['label' => 'Students taught (Home page)', 'value' => '10K+'],
        ['label' => 'Verified tutors (Home page)', 'value' => '100+'],
        ['label' => 'Subjects (Home page)', 'value' => '95+'],
        ['label' => 'Average rating (Home page)', 'value' => '4.8'],
        ['label' => 'Monthly package discount (%)', 'value' => config('pricing.package_discount_percent')],
        ['label' => 'Sibling discount (%)', 'value' => config('pricing.sibling_discount_percent')],
    ];
@endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Admin overview</h1>
        <p>Everything that needs your attention today.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat"><span class="stat__label">Active students</span><span class="stat__value">1,240</span><span class="stat__hint">+86 this month</span></div>
    <div class="stat"><span class="stat__label">Live tutors</span><span class="stat__value">132</span><span class="stat__hint stat__hint--warn">3 waiting for approval</span></div>
    <div class="stat"><span class="stat__label">Classes this week</span><span class="stat__value">418</span><span class="stat__hint">96% completed</span></div>
    <div class="stat"><span class="stat__label">Payments this month</span><span class="stat__value">PKR 2.4M</span><span class="stat__hint">PKR 310K held for upcoming classes</span></div>
</div>

<section class="panel">
    <div class="panel__head"><h2>Tutor applications</h2><a href="#">All applications</a></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Applicant</th><th scope="col">Subjects</th><th scope="col">Documents</th><th scope="col">Stage</th><th scope="col">Applied</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($applications as $a)
                    <tr>
                        <td><strong>{{ $a['name'] }}</strong></td>
                        <td>{{ $a['subjects'] }}</td>
                        <td><span class="status status--{{ $a['docs'][1] }}">{{ $a['docs'][0] }}</span></td>
                        <td>{{ $a['step'] }}</td>
                        <td>{{ $a['date'] }}</td>
                        <td><div class="data-table__actions"><button type="button" class="btn btn-primary btn-sm">Approve</button><button type="button" class="btn btn-outline btn-sm">Reject</button></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Bank transfers to verify</h2><span class="status status--amber">{{ count($transfers) }}</span></div>
        <ul class="item-list">
            @foreach ($transfers as $t)
                <li>
                    <div class="item-list__main"><strong>{{ $t['who'] }} · {{ $t['amount'] }}</strong><span>{{ $t['ref'] }} · {{ $t['date'] }}</span></div>
                    <div class="item-list__side"><button type="button" class="btn btn-outline btn-sm">View screenshot</button><button type="button" class="btn btn-primary btn-sm">Confirm</button></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Refund requests</h2><a href="#">All refunds</a></div>
        <ul class="item-list">
            @foreach ($refunds as $r)
                <li>
                    <div class="item-list__main"><strong>{{ $r['who'] }} · {{ $r['amount'] }}</strong><span>{{ $r['reason'] }}</span></div>
                    <div class="item-list__side"><span class="status status--{{ $r['status'][1] }}">{{ $r['status'][0] }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Complaints</h2><a href="#">All complaints</a></div>
        <ul class="item-list">
            @foreach ($complaints as $c)
                <li>
                    <div class="item-list__main"><strong>{{ $c['subject'] }}</strong><span>{{ $c['by'] }}</span></div>
                    <div class="item-list__side"><span class="status status--{{ $c['status'][1] }}">{{ $c['status'][0] }}</span></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Site settings</h2></div>
        <p class="muted">Figures shown on the public site. Saving comes with the database step.</p>
        <div class="settings-form">
            @foreach ($siteFigures as $i => $f)
                <div class="field">
                    <label for="fig-{{ $i }}">{{ $f['label'] }}</label>
                    <input id="fig-{{ $i }}" class="input" value="{{ $f['value'] }}" disabled>
                </div>
            @endforeach
        </div>
        <div><button type="button" class="btn btn-primary btn-sm" disabled>Save changes</button></div>
    </section>
</div>
@endsection
