{{-- Parent / student: their free demo requests (Step 3a). Expects $demos (DemoRequest collection). --}}
<section class="panel" id="demos">
    <div class="panel__head"><h2>Free demo requests</h2></div>
    @if ($demos->isEmpty())
        <p class="muted">No demo requests yet. Find a tutor and book a free 30-minute demo class.</p>
        <div><a href="{{ route('tutors.index') }}" class="btn btn-primary btn-sm">Find a tutor</a></div>
    @else
        <ul class="item-list">
            @foreach ($demos as $demo)
                @php $status = $demo->currentStatus(); $tutor = $demo->tutorProfile; @endphp
                <li>
                    <div class="item-list__main">
                        <strong>{{ $tutor?->user?->name ?? 'Tutor no longer listed' }} · {{ $demo->subject?->name ?? '—' }}</strong>
                        @if ($status === \App\Enums\DemoStatus::Accepted)
                            <span>For {{ $demo->student_name }} · <strong>{{ $demo->scheduledLabel() }}</strong> ({{ config('site.timezone_label') }})</span>
                        @else
                            <span>For {{ $demo->student_name }} · {{ $demo->preferred_day }} {{ strtolower(\App\Http\Requests\BookDemoRequest::TIMES[$demo->preferred_time] ?? $demo->preferred_time) }} · sent {{ $demo->created_at->format('d M') }}</span>
                        @endif
                        @if ($demo->tutor_note)
                            <span>Tutor: “{{ $demo->tutor_note }}”</span>
                        @endif
                    </div>
                    <div class="item-list__side">
                        @if ($demo->canJoinNow())
                            <a href="{{ route('demos.join', $demo) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Join class</a>
                        @else
                            <span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span>
                        @endif
                        @if ($status === \App\Enums\DemoStatus::Accepted && $demo->scheduled_at->isFuture() && ! $demo->canJoinNow())
                            <span class="muted">Join opens {{ \App\Models\DemoRequest::JOIN_OPENS_BEFORE }} min before</span>
                        @endif
                        @if ($status === \App\Enums\DemoStatus::Pending)
                            <form method="post" action="{{ route('demos.cancel', $demo) }}" data-confirm="Cancel this demo request?">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
