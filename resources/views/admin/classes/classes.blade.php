@extends('layouts.dashboard')

@section('title', 'Classes')

@section('content')
<div class="dash-head">
    <div>
        <h1>Classes</h1>
        <p>Every regular class and its attendance. Times are {{ config('site.timezone_label') }}.</p>
    </div>
    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline btn-sm">See bookings</a>
</div>

@include('admin.classes.tabs', ['route' => 'admin.classes.index'])

<section class="panel">
    @if ($sessions->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Class time</th><th scope="col">Student</th><th scope="col">Tutor</th><th scope="col">Family</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $s)
                        @php $b = $s->booking; @endphp
                        <tr>
                            <td>{{ $s->label() }}</td>
                            <td>{{ $b->student_name }}<br><span class="muted">{{ $b->subject?->name ?? '—' }}</span></td>
                            <td>@if ($b->tutorProfile)<a href="{{ route('admin.tutors.show', $b->tutorProfile->id) }}">{{ $b->tutorProfile->user->name }}</a>@else — @endif</td>
                            <td>{{ $b->user?->name ?? '—' }}</td>
                            <td>
                                @if ($tab === 'unmarked')
                                    <span class="status status--red">Not marked</span>
                                @else
                                    <span class="status status--{{ $s->status->colour() }}">{{ $s->status->label() }}</span>
                                @endif
                                @if ($s->note)<br><span class="muted">“{{ $s->note }}”</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $sessions->links('partials.pagination') }}
    @endif
</section>
@endsection
