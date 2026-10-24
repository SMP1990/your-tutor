<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One regular class (Step 4b). Made only by App\Support\ClassSchedule, never from a form; attendance comes in Step 4c. */
#[Fillable(['class_booking_id', 'starts_at', 'ends_at'])]
class ClassSession extends Model
{
    /** "Join class" works from this many minutes before the start until the end of the class. */
    public const JOIN_OPENS_BEFORE = 15;

    protected $attributes = ['status' => 'scheduled'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /** Classes not yet finished, soonest first. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('status', 'scheduled')->where('ends_at', '>', now())->orderBy('starts_at');
    }

    public function canJoinNow(): bool
    {
        return $this->status === 'scheduled'
            && now()->between($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_BEFORE), $this->ends_at);
    }

    /** "Mon 26 Oct, 5:00 PM" in the site timezone. */
    public function label(): string
    {
        return $this->starts_at->copy()->timezone(config('site.timezone'))->format('D j M, g:i A');
    }

    /** @return BelongsTo<ClassBooking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class, 'class_booking_id');
    }
}
