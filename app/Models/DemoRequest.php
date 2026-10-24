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

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['status' => DemoStatus::class, 'expires_at' => 'datetime'];
    }

    /** Status as the user should see it: a pending request past its time is expired. */
    public function currentStatus(): DemoStatus
    {
        return $this->status === DemoStatus::Pending && $this->expires_at->isPast() ? DemoStatus::Expired : $this->status;
    }

    /** Waiting for the tutor and not expired. */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', DemoStatus::Pending)->where('expires_at', '>', now());
    }

    /** Still counts towards "one free demo per tutor": waiting, accepted or done. */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereIn('status', [DemoStatus::Accepted, DemoStatus::Completed])
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
