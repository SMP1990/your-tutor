@extends('layouts.dashboard')

@section('title', 'Class bookings')

@section('content')
<div class="dash-head">
    <div>
        <h1>Regular class bookings</h1>
        <p>Every weekly-class booking. Times are {{ config('site.timezone_label') }}.</p>
    </div>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline btn-sm">See classes</a>
</div>

@include('admin.classes.tabs', ['route' => 'admin.bookings.index'])

<section class="panel">
    @if ($bookings->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Sent</th><th scope="col">Family</th><th scope="col">Student</th><th scope="col">Tutor</th><th scope="col">Schedule</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $b)
                        @php $status = $b->currentStatus(); @endphp
                        <tr>
                            <td>{{ $b->created_at->copy()->timezone(config('site.timezone'))->format('d M, g:i A') }}</td>
                            <td><strong>{{ $b->user?->name ?? '—' }}</strong><br><span class="muted">{{ $b->user?->email }}</span></td>
                            <td>{{ $b->student_name }}<br><span class="muted">{{ $b->subject?->name ?? '—' }} · {{ $b->grade }}</span></td>
                            <td>@if ($b->tutorProfile)<a href="{{ route('admin.tutors.show', $b->tutorProfile->id) }}">{{ $b->tutorProfile->user->name }}</a>@else — @endif</td>
                            <td>
                                {{ $b->scheduleLabel() }}<br>
                                <span class="muted">from {{ $b->starts_on->format('d M') }} · {{ $b->weeks }} weeks · {{ $b->rateLabel() }}</span>
                                @if ($b->sessions_count)<br><span class="muted">{{ $b->held_count }} of {{ $b->sessions_count }} held</span>@endif
                            </td>
                            <td>
                                <span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span>
                                @if ($b->tutor_note)<br><span class="muted">“{{ $b->tutor_note }}”</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $bookings->links('partials.pagination') }}
    @endif
</section>
@endsection
