@extends('layouts.dashboard')

@section('title', 'Reviews')

@section('content')
<div class="dash-head">
    <div>
        <h1>Reviews</h1>
        <p>Reviews show on tutor profiles as soon as families post them. Hide any review that is wrong, rude or not about the classes; it stops counting in the tutor's rating at once.</p>
    </div>
</div>

@include('admin.classes.tabs', ['route' => 'admin.reviews.index'])

<section class="panel">
    @if ($reviews->isEmpty())
        <p class="muted">Nothing here right now.</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Posted</th><th scope="col">Tutor</th><th scope="col">Rating</th><th scope="col">Review</th><th scope="col">By</th><th scope="col"><span class="sr-only">Action</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $r)
                        <tr>
                            <td>{{ $r->created_at->copy()->timezone(config('site.timezone'))->format('d M Y') }}</td>
                            <td>@if ($r->tutorProfile)<a href="{{ route('admin.tutors.show', $r->tutorProfile->id) }}">{{ $r->tutorProfile->user->name }}</a>@else — @endif</td>
                            <td><span aria-label="{{ $r->rating }} out of 5">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span></td>
                            <td>{{ $r->comment }}</td>
                            <td>{{ $r->user?->name ?? '—' }}<br><span class="muted">shown as {{ $r->reviewerName() }}</span></td>
                            <td>
                                <form method="post" action="{{ route('admin.reviews.toggle', $r) }}" @if (! $r->is_hidden) data-confirm="Hide this review?" @endif>
                                    @csrf
                                    <button type="submit" @class(['btn btn-sm', 'btn-outline btn-danger' => ! $r->is_hidden, 'btn-outline' => $r->is_hidden])>{{ $r->is_hidden ? 'Show again' : 'Hide' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $reviews->links('partials.pagination') }}
    @endif
</section>
@endsection
