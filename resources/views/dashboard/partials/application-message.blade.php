{{-- Expects $profile (TutorProfile|null). Shows the tutor what the review team said or planned. --}}
@if ($profile)
    @switch($profile->status)
        @case(\App\Enums\TutorStatus::DocumentsNeeded)
            <div class="form-note form-note--warn" role="status">
                <strong>Our team needs a little more:</strong> {{ $profile->decision_note }}
                Update your application below and submit it again.
            </div>
            @break
        @case(\App\Enums\TutorStatus::InterviewScheduled)
            <div class="form-note" role="status">
                <strong>Your interview:</strong> {{ $profile->interview_at?->format('l j F Y, g:i A') }} (Pakistan time).
                @if ($profile->interview_link)
                    <a href="{{ $profile->interview_link }}" target="_blank" rel="noopener noreferrer">Join on Google Meet</a>
                @endif
            </div>
            @break
        @case(\App\Enums\TutorStatus::Rejected)
            <div class="form-alert" role="status">
                <strong>We could not approve your application.</strong> {{ $profile->decision_note }}
                If you have questions, <a href="{{ route('contact') }}">contact us</a>.
            </div>
            @break
    @endswitch
@endif
