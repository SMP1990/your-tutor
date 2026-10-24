@extends('layouts.dashboard')

@section('title', 'Demo requests')

@php $times = \App\Http\Requests\BookDemoRequest::TIMES; @endphp

@section('content')
<div class="dash-head">
    <div>
        <h1>Free demo requests</h1>
        <p>Every demo request on the site. Tutors have {{ \App\Models\DemoRequest::ANSWER_HOURS }} hours to answer; times are {{ config('site.timezone_label') }}.</p>
    </div>
</div>

<nav class="child-tabs" aria-label="Demo request status">
    @foreach ($tabs as $key => $t)
        <a href="{{ route('admin.demos.index', ['tab' => $key]) }}" @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-outline' => $tab !== $key]) @if ($tab === $key) aria-current="page" @endif>
            {{ $t['label'] }} ({{ $t['count'] }})
        </a>
    @endforeach
</nav>

<section class="panel">
    @if ($demos->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Sent</th><th scope="col">Family</th><th scope="col">Student</th><th scope="col">Tutor</th><th scope="col">Class</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($demos as $demo)
                        @php $status = $demo->currentStatus(); @endphp
                        <tr>
                            <td>{{ $demo->created_at->copy()->timezone(config('site.timezone'))->format('d M, g:i A') }}</td>
                            <td><strong>{{ $demo->user?->name ?? '—' }}</strong><br><span class="muted">{{ $demo->phone }} · {{ $demo->user?->email }}</span></td>
                            <td>{{ $demo->student_name }}<br><span class="muted">{{ $demo->grade }}</span></td>
                            <td>
                                @if ($demo->tutorProfile)
                                    <a href="{{ route('admin.tutors.show', $demo->tutorProfile->id) }}">{{ $demo->tutorProfile->user->name }}</a>
                                @else — @endif
                            </td>
                            <td>
                                {{ $demo->subject?->name ?? '—' }}<br>
                                <span class="muted">{{ $demo->scheduledLabel() ?? 'Prefers '.$demo->preferred_day.' '.strtolower($times[$demo->preferred_time] ?? $demo->preferred_time) }}</span>
                            </td>
                            <td>
                                <span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span>
                                @if ($status === \App\Enums\DemoStatus::Pending)
                                    <br><span class="muted">answer by {{ $demo->expires_at->copy()->timezone(config('site.timezone'))->format('d M, g:i A') }}</span>
                                @endif
                                @if ($demo->tutor_note)<br><span class="muted">“{{ $demo->tutor_note }}”</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $demos->links('partials.pagination') }}
    @endif
</section>
@endsection
