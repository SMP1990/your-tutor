@extends('layouts.dashboard')

@section('title', 'Admin dashboard')

@section('content')
<div class="dash-head">
    <div>
        <h1>Admin overview</h1>
        <p>Live numbers from the database.</p>
    </div>
</div>

<div class="stat-grid">
    @foreach (\App\Enums\Role::cases() as $role)
        <div class="stat">
            <span class="stat__label">{{ $role->label() }}s</span>
            <span class="stat__value">{{ $usersByRole[$role->value] ?? 0 }}</span>
        </div>
    @endforeach
</div>

<div class="dash-grid dash-grid--halves">
    <section class="panel">
        <div class="panel__head"><h2>Tutors by status</h2>
            @if ($waiting)
                <span class="status status--amber">{{ $waiting }} waiting</span>
            @endif
        </div>
        <ul class="item-list">
            @foreach (\App\Enums\TutorStatus::cases() as $status)
                <li>
                    <div class="item-list__main"><strong>{{ $status->label() }}</strong></div>
                    <div class="item-list__side"><strong>{{ $tutorsByStatus[$status->value] ?? 0 }}</strong></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Tutor approvals</h2>
            @if ($waiting)<span class="status status--amber">{{ $waiting }} waiting</span>@endif
        </div>
        <p class="muted">Review applications, record the subject test and interview, then approve, reject or ask for documents.</p>
        <div><a href="{{ route('admin.tutors.index') }}" class="btn btn-primary btn-sm">Open approvals</a></div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Free demo requests</h2>
            @if ($demoCounts['unanswered'])<span class="status status--red">{{ $demoCounts['unanswered'] }} not answered</span>@endif
        </div>
        <ul class="item-list">
            @foreach (['waiting', 'unanswered', 'outcome'] as $key)
                <li>
                    <div class="item-list__main"><a href="{{ route('admin.demos.index', ['tab' => $key]) }}">{{ \App\Http\Controllers\Admin\DemoRequestController::TABS[$key] }}</a></div>
                    <div class="item-list__side"><strong>{{ $demoCounts[$key] }}</strong></div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Regular classes</h2>
            @if ($classCounts['classes_unmarked'])<span class="status status--red">{{ $classCounts['classes_unmarked'] }} not marked</span>@endif
        </div>
        <ul class="item-list">
            @foreach ([
                ['Bookings waiting for tutor', 'admin.bookings.index', 'waiting', 'bookings_waiting'],
                ['Bookings not answered in time', 'admin.bookings.index', 'unanswered', 'bookings_unanswered'],
                ['Upcoming classes', 'admin.classes.index', 'upcoming', 'classes_upcoming'],
                ['Attendance not marked', 'admin.classes.index', 'unmarked', 'classes_unmarked'],
            ] as [$label, $route, $tab, $key])
                <li>
                    <div class="item-list__main"><a href="{{ route($route, ['tab' => $tab]) }}">{{ $label }}</a></div>
                    <div class="item-list__side"><strong>{{ $classCounts[$key] }}</strong></div>
                </li>
            @endforeach
        </ul>
    </section>
</div>

<section class="panel">
    <div class="panel__head"><h2>Newest accounts</h2></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Email status</th><th scope="col">Joined</th></tr></thead>
            <tbody>
                @foreach ($latestUsers as $u)
                    <tr>
                        <td><strong>{{ $u->name }}</strong></td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->role->label() }}</td>
                        <td><span class="status status--{{ $u->email_verified_at ? 'green' : 'amber' }}">{{ $u->email_verified_at ? 'Verified' : 'Not verified' }}</span></td>
                        <td>{{ $u->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
