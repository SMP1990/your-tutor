<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A family's review of a tutor (Step 5). Created by ReviewController only; nothing mass-assigned from a form. */
class Review extends Model
{
    public const MIN_COMMENT = 10;

    public const MAX_COMMENT = 1000;

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_hidden' => 'boolean'];
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }

    /** Privacy: first name and first letter of the last name, e.g. "Sadia M." */
    public function reviewerName(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user?->name));
        $first = $parts[0] ?? 'Parent';

        return isset($parts[1]) ? $first.' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.' : $first;
    }

    /** @return BelongsTo<TutorProfile, $this> */
    public function tutorProfile(): BelongsTo
    {
        return $this->belongsTo(TutorProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Child, $this> */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** @return BelongsTo<ClassBooking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class, 'class_booking_id');
    }
}
