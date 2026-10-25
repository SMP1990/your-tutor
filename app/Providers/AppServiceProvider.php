<?php

namespace App\Providers;

use App\Models\TutorProfile;
use App\Support\SiteSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
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
        // {tutor} in URLs = an approved tutor's public data; unknown or unapproved slugs give a 404.
        // Registered here (not in routes/web.php) so it also works when routes are cached in production.
        Route::bind('tutor', fn (string $slug) => TutorProfile::approved()->where('slug', $slug)->first()?->toPublicArray() ?? abort(404));

        // Every email gets "Reply-To: support email" (the admin can change it in Site settings), while the
        // sender stays the no-reply mailbox. A queue worker or the console has no web request, so apply the
        // saved settings here too. Skipped while the support email is still the "[support email]" placeholder.
        Event::listen(MessageSending::class, function (MessageSending $event) {
            SiteSettings::apply();
            $support = (string) config('site.support_email');

            if (filter_var($support, FILTER_VALIDATE_EMAIL) && $event->message->getReplyTo() === []) {
                $event->message->replyTo($support);
            }
        });

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
