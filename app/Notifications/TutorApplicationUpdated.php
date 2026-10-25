<?php

namespace App\Notifications;

use App\Enums\TutorStatus;
use App\Models\TutorProfile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to the tutor when their application status changes or an interview is scheduled. */
class TutorApplicationUpdated extends QueuedNotification
{
    public function __construct(public readonly TutorProfile $profile) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->profile;
        $mail = (new MailMessage)->greeting('Hi '.strtok($notifiable->name, ' ').',');

        return match ($p->status) {
            TutorStatus::Approved => $mail->subject('You are approved | '.config('app.name'))
                ->line('Congratulations! Your tutor profile is approved and students can now find you.')
                ->action('Open your dashboard', route('dashboard')),
            TutorStatus::Rejected => $mail->subject('About your tutor application | '.config('app.name'))
                ->line('Thank you for applying. We are not able to approve your application at this time.')
                ->line('Reason: '.$p->decision_note),
            TutorStatus::DocumentsNeeded => $mail->subject('We need a little more | '.config('app.name'))
                ->line('Before we can continue, please update your application:')
                ->line($p->decision_note)
                ->action('Update your application', route('tutor.application')),
            TutorStatus::InterviewScheduled => $mail->subject('Your interview | '.config('app.name'))
                ->line('Your interview is on '.$p->interview_at?->format('l j F Y, g:i A').' ('.config('site.timezone_label').').')
                ->action('Join the interview', $p->interview_link ?: route('dashboard'))
                ->line('Please join from a quiet place with your camera on.'),
            default => $mail->subject('Your tutor application | '.config('app.name'))
                ->line('Your application status is now: '.$p->status->label().'.'),
        };
    }
}
