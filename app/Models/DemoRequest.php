<?php

namespace App\Models;

use App\Enums\DemoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A free demo class request (Step 3a). Created only by DemoBookingController; nothing is mass-assigned from a form. */
class DemoRequest extends Model
{
    /** Hours a tutor has to answer before the request expires (owner decision). */
    public const ANSWER_HOURS = 48;

    /** Most requests one account may have waiting at the same time (owner decision). */
    public const MAX_OPEN = 3;

    /** "Join class" works from this many minutes before the start ... */
    public const JOIN_OPENS_BEFORE = 15;

    /** ... until this many minutes after it (a demo lasts 30 minutes). */
    public const JOIN_CLOSES_AFTER = 60;

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'status' => DemoStatus::class,
            'expires_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /** The class time as people read it, e.g. "Sat 26 Oct, 5:00 PM" (site timezone, stored in UTC). */
    public function scheduledLabel(): ?string
    {
        return $this->scheduled_at?->copy()->timezone(config('site.timezone'))->format('D j M, g:i A');
    }

    /** Status as the user should see it: a pending request past its time is expired. */
    public function currentStatus(): DemoStatus
    {
        return $this->status === DemoStatus::Pending && $this->expires_at->isPast() ? DemoStatus::Expired : $this->status;
    }

    /** Accepted demo whose join window is open now. */
    public function canJoinNow(): bool
    {
        return $this->status === DemoStatus::Accepted && $this->scheduled_at
            && now()->between($this->scheduled_at->copy()->subMinutes(self::JOIN_OPENS_BEFORE), $this->scheduled_at->copy()->addMinutes(self::JOIN_CLOSES_AFTER));
    }

    /** Accepted, and the class time has started: the tutor may now say whether it happened. */
    public function awaitsOutcome(): bool
    {
        return $this->status === DemoStatus::Accepted && $this->scheduled_at?->isPast();
    }

    /** Waiting for the tutor and not expired. */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', DemoStatus::Pending)->where('expires_at', '>', now());
    }

    /** Still counts towards "one free demo per tutor": waiting, accepted, done or missed by the student. */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereIn('status', [DemoStatus::Accepted, DemoStatus::Completed, DemoStatus::NoShow])
            ->orWhere(fn (Builder $p) => $p->where('status', DemoStatus::Pending)->where('expires_at', '>', now())));
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

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
