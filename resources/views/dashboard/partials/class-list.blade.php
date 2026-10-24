{{-- A list of classes with "Join class". Expects $sessions (ClassSession items) and $isTutor. --}}
@if ($sessions->isEmpty())
    <p class="muted">No upcoming classes. {{ $isTutor ? 'Confirmed bookings show here as dated classes.' : 'Once a tutor confirms your regular classes, they show here.' }}</p>
@else
    <ul class="item-list">
        @foreach ($sessions as $session)
            @php $b = $session->booking; @endphp
            <li>
                <span class="date-chip" aria-hidden="true">
                    <strong>{{ $session->starts_at->copy()->timezone(config('site.timezone'))->format('j') }}</strong>
                    <span>{{ $session->starts_at->copy()->timezone(config('site.timezone'))->format('M') }}</span>
                </span>
                <div class="item-list__main">
                    <strong>{{ $session->label() }} · {{ $b->subject?->name ?? 'Class' }}</strong>
                    <span>{{ $isTutor ? $b->student_name : 'With '.($b->tutorProfile?->user?->name ?? 'your tutor').' · for '.$b->student_name }} · {{ $b->duration_minutes }} min</span>
                </div>
                <div class="item-list__side">
                    @if ($session->canJoinNow())
                        <a href="{{ route('classes.join', $session) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Join class</a>
                    @else
                        <span class="muted">Join opens {{ \App\Models\ClassSession::JOIN_OPENS_BEFORE }} min before</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
