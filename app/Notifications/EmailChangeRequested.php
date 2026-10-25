<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Tells the OLD address that somebody asked to move the account to another email (a warning if it was not you). */
class EmailChangeRequested extends QueuedNotification
{
    public function __construct(public readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $masked = preg_replace('/(?<=.).(?=[^@]*@)/', '*', $this->newEmail);

        return (new MailMessage)
            ->subject('Email change requested | '.config('app.name'))
            ->greeting('Hi '.strtok($notifiable->name, ' ').',')
            ->line("A request was made to change the email of your account to {$masked}.")
            ->line('Nothing changes until the link sent to that new address is opened. If this was not you, change your password now.')
            ->action('Open your profile', route('profile.edit'));
    }
}
