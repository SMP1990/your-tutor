<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one plain test email so the owner can see at once whether the mail settings in .env work
 * (Step 7b). Prints the settings it used, never the password.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {to : Email address that should receive the test}';

    protected $description = 'Send a test email using the mail settings in .env';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');
        $this->line("Mailer: {$mailer}".($mailer === 'smtp' ? ' ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').')' : ''));
        $this->line("From:   {$from}");

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\": nothing is really sent, the email is only written to the log. Set MAIL_MAILER=smtp in .env.");
        }
        if (preg_match('/@(example\.(com|org|net)|.*\.test)$/i', $from)) {
            $this->warn('MAIL_FROM_ADDRESS still looks like a placeholder. Use a real mailbox on your domain.');
        }

        try {
            Mail::raw('This is a test email from '.config('app.name').'. If you can read it, sending works.', function ($message) use ($to) {
                $message->to($to)->subject('Test email | '.config('app.name'));
            });
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Handed to the mail server for {$to}. Check the inbox (and the spam folder).");

        return self::SUCCESS;
    }
}
