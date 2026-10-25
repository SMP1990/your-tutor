<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent at once (not queued) to the NEW address. The signed link only works while that address is still the
 * pending one, so an old link stops working when the person asks for a different address.
 */
class ConfirmNewEmail extends Notification
{
    public function __construct(public readonly User $user, public readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function url(User $user, string $newEmail): string
    {
        return URL::temporarySignedRoute('profile.email.confirm', now()->addMinutes(config('auth.verification.expire', 60)), [
            'user' => $user->id,
            'hash' => sha1($newEmail),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new email | '.config('app.name'))
            ->greeting('Hi '.strtok($this->user->name, ' ').',')
            ->line('Please confirm that this is your new email address for '.config('app.name').'.')
            ->action('Confirm new email', self::url($this->user, $this->newEmail))
            ->line('The link works for '.config('auth.verification.expire', 60).' minutes. Until you open it, you keep logging in with your old email.')
            ->line('If you did not ask for this, ignore this email.');
    }
}
