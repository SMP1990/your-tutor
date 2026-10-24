@extends('layouts.dashboard')

@section('title', 'Tutor approvals')

@section('content')
<div class="dash-head">
    <div>
        <h1>Tutor approvals</h1>
        <p>Check each application, record the subject test and interview, then decide.</p>
    </div>
</div>

<nav class="child-tabs" aria-label="Application status">
    @foreach ($tabs as $key => $t)
        <a href="{{ route('admin.tutors.index', ['tab' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-outline' => $tab !== $key]) @if ($tab === $key) aria-current="page" @endif>
            {{ $t['label'] }} ({{ $t['count'] }})
        </a>
    @endforeach
</nav>

<section class="panel">
    @if ($profiles->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Tutor</th><th scope="col">Subjects</th><th scope="col">Submitted</th><th scope="col">Test</th><th scope="col">Interview</th><th scope="col"><span class="sr-only">Open</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($profiles as $p)
                        <tr>
                            <td><strong>{{ $p->user->name }}</strong><br><span class="muted">{{ $p->city ?: '—' }}</span></td>
                            <td>{{ $p->subjects->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td>{{ $p->submitted_at?->format('d M Y') ?? '—' }}</td>
                            <td>@include('admin.tutors.result', ['result' => $p->subject_test_result])</td>
                            <td>
                                @include('admin.tutors.result', ['result' => $p->interview_result])
                                @if ($p->interview_at && ! $p->interview_result)<br><span class="muted">{{ $p->interview_at->format('d M, g:i A') }}</span>@endif
                            </td>
                            <td><div class="data-table__actions"><a href="{{ route('admin.tutors.show', $p->id) }}" class="btn btn-primary btn-sm">Open</a></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $profiles->links('partials.pagination') }}
    @endif
</section>
@endsection
