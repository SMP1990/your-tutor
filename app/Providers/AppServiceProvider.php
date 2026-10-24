<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(fn (object $user, string $url) => (new MailMessage)
            ->subject('Verify your email | '.config('app.name'))
            ->greeting('Hi '.strtok($user->name, ' ').',')
            ->line('Thanks for joining '.config('app.name').'. Please confirm this is your email address.')
            ->action('Verify email address', $url)
            ->line('The link works for '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not create an account, you can ignore this email.'));

        ResetPassword::toMailUsing(fn (object $user, string $token) => (new MailMessage)
            ->subject('Reset your password | '.config('app.name'))
            ->greeting('Hi '.strtok($user->name, ' ').',')
            ->line('We received a request to reset your password.')
            ->action('Set a new password', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('The link works for '.config('auth.passwords.users.expire', 60).' minutes.')
            ->line('If you did not ask for this, ignore this email. Your password stays the same.'));
    }
}
