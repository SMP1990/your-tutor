<?php

namespace App\Support;

use App\Enums\ClassStatus;
use App\Models\ClassBooking;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TutorProfile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Step 6d: settings the admin edits (table `settings`). Whatever is not set there keeps the value from
 * config/site.php and .env, so a fresh server works before anyone opens the settings page.
 */
class SiteSettings
{
    /** Contact details: setting key = config('site.<key>'). */
    public const CONTACT_KEYS = ['whatsapp_number', 'whatsapp_display', 'support_email', 'support_hours'];

    /** The four numbers under the Home page search box. */
    public const STATS = [
        'students' => 'Students taught',
        'tutors' => 'Verified tutors',
        'subjects' => 'Subjects',
        'rating' => 'Average rating',
    ];

    private const CACHE_KEY = 'site_settings';

    /** Puts the saved contact details into config('site.*'). Called on every web request. */
    public static function apply(): void
    {
        $stored = self::stored();
        // For each key we replace, remember what .env / config said, so clearing the setting brings it back.
        $base = config('site.__base', []);

        foreach (self::CONTACT_KEYS as $key) {
            if (filled($stored[$key] ?? null)) {
                if (! array_key_exists($key, $base)) {
                    $base[$key] = config("site.{$key}");
                }
                config(["site.{$key}" => $stored[$key]]);
            } elseif (array_key_exists($key, $base)) {
                config(["site.{$key}" => $base[$key]]);
                unset($base[$key]);
            }
        }

        config(['site.__base' => $base]);
    }

    /** @return array<string, ?string> */
    public static function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('settings')->pluck('value', 'key')->all());
        } catch (QueryException) {
            return []; // table not created yet (before the first migrate)
        }
    }

    /** Saves the given values; null or '' removes a setting so the default comes back. */
    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                DB::table('settings')->where('key', $key)->delete();
            } else {
                DB::table('settings')->upsert([['key' => $key, 'value' => (string) $value, 'updated_at' => now()]], ['key'], ['value', 'updated_at']);
            }
        }
        Cache::forget(self::CACHE_KEY);
    }

    /** 'auto' (counted from the database) or 'manual' (typed by the owner). New sites start manual. */
    public static function statMode(string $stat): string
    {
        return (self::stored()["stats_{$stat}_mode"] ?? 'manual') === 'auto' ? 'auto' : 'manual';
    }

    /** The typed value, or the placeholder from config/site.php. */
    public static function statManual(string $stat): string
    {
        return (string) (self::stored()["stats_{$stat}_value"] ?? config("site.stats.{$stat}"));
    }

    /** Counted from real data; null when there is nothing worth showing yet. */
    public static function statAuto(string $stat): ?string
    {
        $value = match ($stat) {
            'students' => ClassBooking::whereHas('sessions', fn ($s) => $s->where('status', ClassStatus::Held))
                ->get(['user_id', 'child_id'])->unique(fn ($b) => $b->user_id.'-'.$b->child_id)->count(),
            'tutors' => TutorProfile::approved()->where('is_demo', false)->count(),
            'subjects' => Subject::where('is_active', true)->count(),
            'rating' => ($avg = Review::visible()->avg('rating')) ? number_format((float) $avg, 1) : null,
        };

        return $value ? (string) $value : null;
    }

    /** @return list<array{0: string, 1: string}> [value, label] for the Home page; hides numbers that are empty. */
    public static function homeStats(): array
    {
        $stats = [];
        foreach (self::STATS as $stat => $label) {
            $value = self::statMode($stat) === 'auto' ? self::statAuto($stat) : self::statManual($stat);
            if ($value !== null && $value !== '') {
                $stats[] = [$value, $label];
            }
        }

        return $stats;
    }
}
