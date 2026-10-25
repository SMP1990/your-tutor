@extends('layouts.dashboard')

@section('title', 'Users')

@section('content')
<div class="dash-head">
    <div>
        <h1>Users</h1>
        <p>Every account on the site. Search by name, email or phone. Accounts are never deleted; you can suspend one instead.</p>
    </div>
</div>

<section class="panel">
    <form method="get" action="{{ route('admin.users.index') }}" class="demo-form" role="search">
        <div class="field">
            <label for="q">Search</label>
            <input id="q" name="q" type="search" class="input" maxlength="80" value="{{ $q }}" placeholder="Name, email or phone">
        </div>
        <div class="field">
            <label for="role">Role</label>
            <select id="role" name="role" class="input">
                <option value="">All roles</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->value }}" @selected($role === $r->value)>{{ $r->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" class="input">
                <option value="">Any status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Clear</a>
        </div>
    </form>
</section>

<section class="panel">
    @if ($users->isEmpty())
        <p class="muted">No users match.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Email</th><th scope="col">Phone</th><th scope="col">Joined</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Open</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><strong>{{ $u->name }}</strong></td>
                            <td>{{ $u->role->label() }}</td>
                            <td>{{ $u->email }}<br>@if (! $u->hasVerifiedEmail())<span class="status status--amber">Not verified</span>@endif</td>
                            <td>{{ $u->phone ?: '—' }}</td>
                            <td>{{ $u->created_at->copy()->timezone(config('site.timezone'))->format('d M Y') }}</td>
                            <td>@if ($u->isSuspended())<span class="status status--red">Suspended</span>@else<span class="status status--green">Active</span>@endif</td>
                            <td><a href="{{ route('admin.users.show', $u) }}" class="btn btn-outline btn-sm">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('partials.pagination') }}
    @endif
</section>
@endsection
