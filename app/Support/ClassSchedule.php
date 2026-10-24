<?php

namespace App\Support;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use Illuminate\Support\Carbon;

/**
 * Step 4b: turns a confirmed booking into its classes. "Week 1" is the 7 days from the start date, so each
 * chosen weekday happens exactly once per week: days x weeks classes in all.
 */
class ClassSchedule
{
    /** @return list<array{starts_at: Carbon, ends_at: Carbon}> times in UTC, soonest first */
    public static function times(ClassBooking $booking): array
    {
        $tz = config('site.timezone');
        $first = Carbon::parse($booking->starts_on->toDateString(), $tz);
        $times = [];

        for ($week = 0; $week < $booking->weeks; $week++) {
            for ($offset = 0; $offset < 7; $offset++) {
                $day = $first->copy()->addDays($week * 7 + $offset);
                if (in_array($day->format('D'), $booking->days, true)) {
                    $start = Carbon::parse($day->toDateString().' '.$booking->start_time, $tz);
                    $times[] = ['starts_at' => $start->copy()->utc(), 'ends_at' => $start->copy()->addMinutes($booking->duration_minutes)->utc()];
                }
            }
        }

        return $times;
    }

    /** Creates the classes once; safe to call again (existing ones are kept). */
    public static function create(ClassBooking $booking): int
    {
        foreach (self::times($booking) as $t) {
            ClassSession::firstOrCreate(
                ['class_booking_id' => $booking->id, 'starts_at' => $t['starts_at']],
                ['ends_at' => $t['ends_at']],
            );
        }

        return $booking->sessions()->count();
    }
}
