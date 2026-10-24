<?php

namespace App\Models;

use App\Enums\ClassStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One regular class (Step 4b). Made only by App\Support\ClassSchedule, never from a form.
 * Attendance (Step 4c) is set by code through mark(); status is not fillable.
 */
#[Fillable(['class_booking_id', 'starts_at', 'ends_at'])]
class ClassSession extends Model
{
    /** "Join class" works from this many minutes before the start until the end of the class. */
    public const JOIN_OPENS_BEFORE = 15;

    /** A family may cancel a class up to this many hours before it starts (owner may change). */
    public const FAMILY_CANCEL_HOURS = 12;

    protected $attributes = ['status' => 'scheduled'];

    protected function casts(): array
    {
        return [
            'status' => ClassStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'marked_at' => 'datetime',
        ];
    }

    /** Classes not yet finished, soonest first. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('status', ClassStatus::Scheduled)->where('ends_at', '>', now())->orderBy('starts_at');
    }

    /** Started classes nobody marked yet: the tutor should say what happened. */
    public function scopeToMark(Builder $query): void
    {
        $query->where('status', ClassStatus::Scheduled)->where('starts_at', '<=', now())->orderBy('starts_at');
    }

    /** Classes with an outcome (or cancelled), newest first. */
    public function scopePast(Builder $query): void
    {
        $query->where('status', '!=', ClassStatus::Scheduled)->orderByDesc('starts_at');
    }

    public function canJoinNow(): bool
    {
        return $this->status === ClassStatus::Scheduled
            && now()->between($this->starts_at->copy()->subMinutes(self::JOIN_OPENS_BEFORE), $this->ends_at);
    }

    /** Held / absent can only be said once the class has started; the tutor may cancel any scheduled class. */
    public function tutorMayMark(ClassStatus $status): bool
    {
        if ($this->status !== ClassStatus::Scheduled) {
            return false;
        }

        return match ($status) {
            ClassStatus::Held, ClassStatus::StudentAbsent => $this->starts_at->isPast(),
            ClassStatus::TutorCancelled => true,
            default => false,
        };
    }

    public function familyMayCancel(): bool
    {
        return $this->status === ClassStatus::Scheduled
            && $this->starts_at->copy()->subHours(self::FAMILY_CANCEL_HOURS)->isFuture();
    }

    public function mark(ClassStatus $status, User $by, ?string $note = null): void
    {
        $this->forceFill(['status' => $status, 'note' => $note, 'marked_by' => $by->id, 'marked_at' => now()])->save();
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
