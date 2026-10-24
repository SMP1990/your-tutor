<?php

namespace App\Providers;

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
            ->subject('Verify your email for '.config('app.name'))
            ->greeting('Hi '.strtok($user->name, ' ').',')
            ->line('Thanks for joining '.config('app.name').'. Please confirm this is your email address.')
            ->action('Verify email address', $url)
            ->line('The link works for '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not create an account, you can ignore this email.'));
    }
}
