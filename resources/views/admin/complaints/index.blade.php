@extends('layouts.dashboard')

@section('title', 'Complaints')

@section('content')
<div class="dash-head">
    <div>
        <h1>Complaints</h1>
        <p>Problems reported by families and tutors. Write a reply and they read it on their dashboard.</p>
    </div>
</div>

@include('admin.classes.tabs', ['route' => 'admin.complaints.index'])

<section class="panel">
    @if ($complaints->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Reported</th><th scope="col">By</th><th scope="col">About</th><th scope="col">Problem</th><th scope="col"><span class="sr-only">Open</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($complaints as $c)
                        <tr>
                            <td>{{ $c->created_at->copy()->timezone(config('site.timezone'))->format('d M Y') }}</td>
                            <td>{{ $c->user?->name ?? '—' }}<br><span class="muted">{{ $c->user?->role->label() }}</span></td>
                            <td>Tutor {{ $c->booking?->tutorProfile?->user?->name ?? '—' }}</td>
                            <td><strong>{{ $c->categoryLabel() }}</strong><br>{{ \Illuminate\Support\Str::limit($c->details, 90) }}</td>
                            <td><a href="{{ route('admin.complaints.show', $c) }}" class="btn btn-outline btn-sm">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $complaints->links('partials.pagination') }}
    @endif
</section>
@endsection
