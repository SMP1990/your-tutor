@extends('layouts.dashboard')

@section('title', 'Messages')

@section('content')
<div class="dash-head">
    <div>
        <h1>Messages</h1>
        <p>Messages from the Contact page. Reply by WhatsApp or from your own email, then mark the message answered.</p>
    </div>
</div>

@include('admin.classes.tabs', ['route' => 'admin.contact.index'])

<section class="panel">
    @if ($messages->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Received</th><th scope="col">From</th><th scope="col">Topic</th><th scope="col">Message</th><th scope="col"><span class="sr-only">Open</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($messages as $m)
                        <tr>
                            <td>{{ $m->created_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}</td>
                            <td><strong>{{ $m->name }}</strong><br><span class="muted">{{ $m->phone ?: $m->email }}</span></td>
                            <td>{{ $m->topic }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($m->message, 100) }}</td>
                            <td><a href="{{ route('admin.contact.show', $m) }}" class="btn btn-outline btn-sm">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $messages->links('partials.pagination') }}
    @endif
</section>
@endsection
