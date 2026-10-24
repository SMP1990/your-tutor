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
                    @if ($withActions ?? false)
                        @if ($isTutor)
                            <details class="class-cancel">
                                <summary class="btn btn-outline btn-sm">Cancel class</summary>
                                <form method="post" action="{{ route('classes.mark', $session) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="tutor_cancelled">
                                    <label class="sr-only" for="why-{{ $session->id }}">Reason</label>
                                    <input id="why-{{ $session->id }}" name="note" class="input" maxlength="300" required placeholder="Reason, e.g. I am unwell">
                                    <button type="submit" class="btn btn-outline btn-sm btn-danger">Confirm cancel</button>
                                </form>
                            </details>
                        @elseif ($session->familyMayCancel())
                            <form method="post" action="{{ route('classes.cancel', $session) }}" data-confirm="Cancel the class on {{ $session->label() }}?">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">Cancel class</button>
                            </form>
                        @endif
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
