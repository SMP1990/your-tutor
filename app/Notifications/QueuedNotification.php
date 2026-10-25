<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Base for emails that can wait a minute: they go on the queue (a cron runs the worker, see docs/DEPLOY.md)
 * so a slow or broken mail server never slows down or breaks the page the person is on.
 * Emails that must arrive at once (admin login code, email verification) do NOT extend this.
 */
abstract class QueuedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Try again if the mail server is busy. */
    public int $tries = 3;

    /** @return list<int> Seconds to wait before the 2nd and 3rd try. */
    public function backoff(): array
    {
        return [60, 300];
    }
}
