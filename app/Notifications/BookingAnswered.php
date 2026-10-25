<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\ClassBooking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to the family after the tutor confirmed or declined their regular-classes booking. */
class BookingAnswered extends QueuedNotification
{
    public function __construct(public readonly ClassBooking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;
        $tutor = $b->tutorProfile->user->name;
        $mail = (new MailMessage)->greeting('Hi '.strtok($notifiable->name, ' ').',');

        if ($b->status === BookingStatus::Accepted) {
            $mail->subject('Your regular classes are confirmed | '.config('app.name'))
                ->line("{$tutor} confirmed regular classes for {$b->student_name}.")
                ->line($b->scheduleLabel().' ('.config('site.timezone_label').'), from '.$b->starts_on->format('D j M').' for '.$b->weeks.' weeks.');
        } else {
            $mail->subject('About your class booking | '.config('app.name'))
                ->line("{$tutor} cannot take these classes for {$b->student_name}.");
        }
        if ($b->tutor_note) {
            $mail->line('Message from the tutor: '.$b->tutor_note);
        }

        return $mail->action('Open your dashboard', route('dashboard'));
    }
}
