<?php

namespace App\Notifications;

use App\Enums\DemoStatus;
use App\Models\DemoRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to the parent or student after the tutor accepted or declined their free demo request. */
class DemoRequestAnswered extends QueuedNotification
{
    public function __construct(public readonly DemoRequest $demo) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->demo;
        $tutor = $d->tutorProfile->user->name;
        $mail = (new MailMessage)->greeting('Hi '.strtok($notifiable->name, ' ').',');

        if ($d->status === DemoStatus::Accepted) {
            $mail->subject('Your free demo is confirmed | '.config('app.name'))
                ->line("{$tutor} accepted the free demo for {$d->student_name}.")
                ->line('Time: '.$d->scheduledLabel().' ('.config('site.timezone_label').').')
                ->line('The class is on Google Meet. Join from your dashboard a few minutes early.');
        } else {
            $mail->subject('About your free demo request | '.config('app.name'))
                ->line("{$tutor} cannot take the free demo for {$d->student_name}.");
        }
        if ($d->tutor_note) {
            $mail->line('Message from the tutor: '.$d->tutor_note);
        }

        return $mail->action('Open your dashboard', route('dashboard'));
    }
}
