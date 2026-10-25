<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BookingAnswered;
use App\Notifications\DemoRequestAnswered;
use App\Notifications\QueuedNotification;
use App\Notifications\QueuedResetPassword;
use App\Notifications\TutorApplicationUpdated;
use App\Notifications\TutorChangesReviewed;
use App\Notifications\TwoFactorCodeNotification;
use App\Support\SiteSettings;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

/** Step 7b-1: slow mail never blocks a page; urgent mails go at once; a broken mail server never gives a 500. */
class MailQueueTest extends TestCase
{
    use RefreshDatabase;

    /** Makes every email fail, like a mail server that is down. */
    private function breakMail(): void
    {
        Event::listen(MessageSending::class, fn () => throw new RuntimeException('SMTP is down'));
    }

    public function test_slow_emails_are_queued_and_urgent_ones_are_not(): void
    {
        foreach ([TutorChangesReviewed::class, TutorApplicationUpdated::class, DemoRequestAnswered::class, BookingAnswered::class] as $class) {
            $this->assertTrue(is_subclass_of($class, QueuedNotification::class), "{$class} should be queued");
        }
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new TwoFactorCodeNotification('123456'));
        $this->assertNotInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new VerifyEmail);
        $this->assertSame(3, (new TutorChangesReviewed(true))->tries);
        $this->assertSame([60, 300], (new TutorChangesReviewed(true))->backoff());
    }

    public function test_forgot_password_puts_the_email_on_the_queue(): void
    {
        Queue::fake();
        User::factory()->create(['email' => 'sadia@example.test']);

        $this->post('/forgot-password', ['email' => 'sadia@example.test'])->assertRedirect();

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof QueuedResetPassword);
    }

    public function test_a_broken_mail_server_does_not_break_forgot_password(): void
    {
        User::factory()->create(['email' => 'sadia@example.test']);
        config(['queue.default' => 'database']); // like the live server: the email waits for the cron worker
        $this->breakMail();

        $this->post('/forgot-password', ['email' => 'sadia@example.test'])->assertRedirect()->assertSessionHas('notice');

        $this->assertDatabaseCount('jobs', 1); // the page only queued it; the broken mail server was not touched
    }

    public function test_registering_works_when_the_mail_server_is_down(): void
    {
        $this->breakMail();

        $this->post('/register', [
            'name' => 'Sadia Malik', 'email' => 'sadia@example.test', 'phone' => '0300 1234567', 'role' => 'parent',
            'password' => 'Secret123', 'password_confirmation' => 'Secret123', 'terms' => '1',
        ])->assertRedirect(route('verification.notice'))
            ->assertSessionHas('notice', \App\Http\Controllers\Auth\EmailVerificationController::NOT_SENT);

        $this->assertDatabaseHas('users', ['email' => 'sadia@example.test']);
        $this->assertAuthenticated();
    }

    public function test_resending_the_link_says_so_when_the_mail_server_is_down(): void
    {
        $user = User::factory()->unverified()->create();
        $this->breakMail();

        $this->actingAs($user)->post('/email/verification-notification')
            ->assertRedirect()->assertSessionHas('notice', \App\Http\Controllers\Auth\EmailVerificationController::NOT_SENT);
    }

    public function test_the_admin_code_page_still_opens_when_mail_is_down_and_no_code_is_kept(): void
    {
        $admin = User::factory()->admin()->create();
        $this->breakMail();

        $this->actingAs($admin)->get('/two-factor')->assertOk()->assertSee('We could not send the email right now');
        $this->assertFalse(\App\Support\TwoFactorCode::hasActiveCode($admin));

        $this->actingAs($admin)->post('/two-factor/resend')->assertSessionHasErrors('code');
    }

    public function test_every_email_asks_for_replies_to_the_support_address(): void
    {
        SiteSettings::save(['support_email' => 'help@example.test']);

        Mail::raw('Hello', fn ($m) => $m->to('someone@example.test')->subject('Hi'));

        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame('help@example.test', $sent->getReplyTo()[0]->getAddress());
    }

    public function test_no_reply_to_is_added_while_the_support_email_is_a_placeholder(): void
    {
        config(['site.support_email' => '[support email]']);

        Mail::raw('Hello', fn ($m) => $m->to('someone@example.test')->subject('Hi'));

        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame([], $sent->getReplyTo());
    }

    public function test_mail_test_command(): void
    {
        $this->artisan('mail:test', ['to' => 'someone@example.test'])
            ->expectsOutputToContain('Mailer: array')
            ->expectsOutputToContain('nothing is really sent')
            ->expectsOutputToContain('looks like a placeholder')
            ->assertSuccessful();

        $this->artisan('mail:test', ['to' => 'not-an-email'])->assertFailed();

        $this->breakMail();
        $this->artisan('mail:test', ['to' => 'someone@example.test'])
            ->expectsOutputToContain('Sending failed: SMTP is down')
            ->assertFailed();
    }
}
