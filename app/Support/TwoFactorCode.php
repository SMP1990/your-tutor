<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * 6-digit login code sent by email (free). Stored hashed in the cache for 10 minutes,
 * 5 wrong tries cancel it. Another channel (SMS, authenticator app) can replace the sender later.
 */
class TwoFactorCode
{
    public const MINUTES = 10;

    private const MAX_TRIES = 5;

    private const SESSION_KEY = 'two_factor_passed';

    /** Sends the code at once (not queued). False if the mail server failed; the code is then thrown away. */
    public static function send(User $user): bool
    {
        $code = (string) random_int(100000, 999999);
        Cache::put(self::key($user), ['hash' => Hash::make($code), 'tries' => 0], now()->addMinutes(self::MINUTES));

        try {
            $user->notifyNow(new TwoFactorCodeNotification($code));
        } catch (Throwable $e) {
            report($e);
            Cache::forget(self::key($user));

            return false;
        }

        return true;
    }

    public static function hasActiveCode(User $user): bool
    {
        return Cache::has(self::key($user));
    }

    public static function check(User $user, string $code): bool
    {
        $entry = Cache::get(self::key($user));
        if (! $entry) {
            return false;
        }

        if (Hash::check($code, $entry['hash'])) {
            Cache::forget(self::key($user));

            return true;
        }

        $entry['tries']++;
        $entry['tries'] >= self::MAX_TRIES
            ? Cache::forget(self::key($user))
            : Cache::put(self::key($user), $entry, now()->addMinutes(self::MINUTES));

        return false;
    }

    public static function markPassed(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);
    }

    public static function passed(Request $request): bool
    {
        return $request->session()->get(self::SESSION_KEY) === true;
    }

    private static function key(User $user): string
    {
        return "two-factor-code:{$user->id}";
    }
}
