{{-- Parent / student: their regular-class bookings (Step 4a). Expects $bookings (ClassBooking collection). --}}
@if ($bookings->isNotEmpty())
    <section class="panel" id="bookings">
        <div class="panel__head"><h2>Regular classes</h2></div>
        <ul class="item-list">
            @foreach ($bookings as $booking)
                @php $status = $booking->currentStatus(); @endphp
                <li>
                    <div class="item-list__main">
                        <strong>{{ $booking->tutorProfile?->user?->name ?? 'Tutor' }} · {{ $booking->subject?->name ?? '—' }}</strong>
                        <span>For {{ $booking->student_name }} · {{ $booking->scheduleLabel() }} · from {{ $booking->starts_on->format('D j M') }}, {{ $booking->weeks }} weeks</span>
                        @if ($booking->sessions_count)
                            <span>{{ $booking->held_count }} of {{ $booking->sessions_count }} classes held · <a href="{{ route('classes.index') }}">see classes</a></span>
                        @endif
                        @if ($booking->tutor_note)<span>Tutor: “{{ $booking->tutor_note }}”</span>@endif
                    </div>
                    <div class="item-list__side">
                        <span class="status status--{{ $status->colour() }}">{{ $status->label() }}</span>
                        @if ($status === \App\Enums\BookingStatus::Pending)
                            <form method="post" action="{{ route('bookings.cancel', $booking) }}" data-confirm="Cancel this booking request?">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
