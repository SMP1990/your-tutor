<?php

namespace App\Notifications;

use App\Support\TwoFactorCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your login code | '.config('app.name'))
            ->greeting('Hi '.strtok($notifiable->name, ' ').',')
            ->line('Your login code is:')
            ->line('**'.$this->code.'**')
            ->line('It works for '.TwoFactorCode::MINUTES.' minutes. Never share this code with anyone.')
            ->line('If you did not try to log in, change your password now.');
    }
}
