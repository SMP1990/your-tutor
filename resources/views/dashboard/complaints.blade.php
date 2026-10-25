@extends('layouts.dashboard')

@section('title', 'My reports')

@section('content')
<div class="dash-head">
    <div>
        <h1>My reports</h1>
        <p>Problems you told us about, and our replies. To report something new, use "Report a problem" next to a booking or a class.</p>
    </div>
</div>

<section class="panel">
    @if ($complaints->isEmpty())
        <p class="muted">You have not reported anything.</p>
    @else
        <ul class="item-list">
            @foreach ($complaints as $c)
                <li>
                    <div class="item-list__main">
                        <strong>{{ $c->categoryLabel() }}</strong>
                        <span>
                            {{ $c->created_at->copy()->timezone(config('site.timezone'))->format('d M Y') }}
                            · {{ $c->booking?->subject?->name ?? 'Class' }}
                            @if ($c->session) · class on {{ $c->session->label() }} @endif
                        </span>
                        <span>“{{ $c->details }}”</span>
                        @if ($c->admin_reply)
                            <span><strong>Our reply</strong> ({{ $c->replied_at?->copy()->timezone(config('site.timezone'))->format('d M Y') }}): {{ $c->admin_reply }}</span>
                        @endif
                    </div>
                    <div class="item-list__side"><span class="status status--{{ $c->status->colour() }}">{{ $c->status->label() }}</span></div>
                </li>
            @endforeach
        </ul>
        {{ $complaints->links('partials.pagination') }}
    @endif
</section>
@endsection
