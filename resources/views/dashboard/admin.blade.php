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

    @include('dashboard.partials.coming-soon', [
        'title' => 'Approvals, payments and settings',
        'text' => 'Approving tutors comes with the tutor application step; payments, refunds and site settings come later.',
    ])
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
