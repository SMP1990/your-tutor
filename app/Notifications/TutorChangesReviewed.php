<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to an approved tutor after the admin approved or rejected their profile changes. */
class TutorChangesReviewed extends Notification
{
    public function __construct(public readonly bool $approved, public readonly ?string $note = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hi '.strtok($notifiable->name, ' ').',');

        if ($this->approved) {
            $mail->subject('Your profile changes are live | '.config('app.name'))
                ->line('Our team approved your new subjects, education or documents. They now show on your profile.');
        } else {
            $mail->subject('About your profile changes | '.config('app.name'))
                ->line('We could not approve your latest profile changes. Your profile stays as it was.');
        }
        if ($this->note) {
            $mail->line('Message from our team: '.$this->note);
        }

        return $mail->action('Open your profile', route('tutor.application'));
    }
}
