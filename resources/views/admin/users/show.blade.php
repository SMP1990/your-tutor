@extends('layouts.dashboard')

@section('title', $account->name)

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $account->name }}</h1>
        <p>
            {{ $account->role->label() }} ·
            @if ($account->isSuspended())<span class="status status--red">Suspended</span>@else<span class="status status--green">Active</span>@endif
            · joined {{ $account->created_at->copy()->timezone(config('site.timezone'))->format('d M Y') }}
        </p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="dash-grid dash-grid--2">
    <section class="panel">
        <div class="panel__head"><h2>Details</h2></div>
        <dl class="review-list">
            <div><dt>Email</dt><dd>{{ $account->email }} @if ($account->hasVerifiedEmail())<span class="status status--green">Verified</span>@else<span class="status status--amber">Not verified</span>@endif</dd></div>
            <div><dt>Phone</dt><dd>{{ $account->phone ?: '—' }}</dd></div>
            @foreach ($counts as $label => $n)
                <div><dt>{{ $label }}</dt><dd>{{ $n }}</dd></div>
            @endforeach
            @if ($account->tutorProfile)
                <div><dt>Tutor application</dt><dd>{{ $account->tutorProfile->status->label() }} · <a href="{{ route('admin.tutors.show', $account->tutorProfile->id) }}">Open review page</a></dd></div>
            @endif
            @if ($account->children->isNotEmpty())
                <div><dt>Children</dt><dd>{{ $account->children->map(fn ($c) => $c->name.' ('.$c->grade.')')->implode(', ') }}</dd></div>
            @endif
            @if ($account->isSuspended())
                <div><dt>Suspended</dt><dd>{{ $account->suspended_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}: {{ $account->suspended_reason }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Actions</h2></div>

        @if ($canSuspend)
            <form method="post" action="{{ route('admin.users.'.($account->isSuspended() ? 'unsuspend' : 'suspend'), $account) }}" class="demo-form" novalidate>
                @csrf
                <div class="field">
                    <label for="reason_status">{{ $account->isSuspended() ? 'Why is the account being opened again?' : 'Why is the account being suspended?' }}</label>
                    <textarea id="reason_status" name="reason" rows="3" class="input" maxlength="500" required>{{ old('reason') }}</textarea>
                    <p class="field__hint">Saved in the history below. @unless ($account->isSuspended())The user is logged out at once and cannot log in. A suspended tutor disappears from the public pages.@endunless</p>
                </div>
                <div class="form-actions">
                    @if ($account->isSuspended())
                        <button type="submit" class="btn btn-primary btn-sm">Make account active again</button>
                    @else
                        <button type="submit" class="btn btn-outline btn-danger btn-sm" data-confirm-button="Suspend {{ $account->name }}?">Suspend account</button>
                    @endif
                </div>
            </form>
        @else
            <p class="muted">You cannot suspend your own account or another admin's account.</p>
        @endif

        @unless ($account->hasVerifiedEmail())
            <form method="post" action="{{ route('admin.users.verify-email', $account) }}" class="demo-form" novalidate>
                @csrf
                <h3 class="review-sub">Mark email as verified</h3>
                <div class="field">
                    <label for="reason_email">Why? (for example: checked by phone)</label>
                    <textarea id="reason_email" name="reason" rows="2" class="input" maxlength="500" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-outline btn-sm" data-confirm-button="Mark this email as verified?">Mark as verified</button>
                </div>
            </form>
        @endunless
    </section>
</div>

<section class="panel">
    <div class="panel__head"><h2>History</h2></div>
    @if ($account->adminActions->isEmpty())
        <p class="muted">No admin has done anything to this account.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th scope="col">When</th><th scope="col">What</th><th scope="col">By</th><th scope="col">Reason</th></tr></thead>
                <tbody>
                    @foreach ($account->adminActions as $a)
                        <tr>
                            <td>{{ $a->created_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}</td>
                            <td>{{ $a->label() }}</td>
                            <td>{{ $a->admin?->name ?? '—' }}</td>
                            <td>{{ $a->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
