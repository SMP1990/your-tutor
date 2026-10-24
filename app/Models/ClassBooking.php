<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Regular weekly classes asked for after a free demo (Step 4a). Created by code only; nothing mass-assigned. */
class ClassBooking extends Model
{
    /** Choices offered on the booking form (owner may change these later). */
    public const DURATIONS = [60 => '1 hour', 90 => '1.5 hours', 45 => '45 minutes'];

    public const WEEKS = [4 => '4 weeks', 8 => '8 weeks', 12 => '12 weeks'];

    public const MAX_DAYS = 6;

    /** Hours a tutor has to answer before the request expires (same as demos). */
    public const ANSWER_HOURS = DemoRequest::ANSWER_HOURS;

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'days' => 'array',
            'starts_on' => 'date',
            'expires_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /** Status as people should see it: a pending booking past its time is expired. */
    public function currentStatus(): BookingStatus
    {
        return $this->status === BookingStatus::Pending && $this->expires_at->isPast() ? BookingStatus::Expired : $this->status;
    }

    /** Waiting for the tutor or confirmed: blocks a second booking with the same tutor for the same learner. */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('status', BookingStatus::Accepted)
            ->orWhere(fn (Builder $p) => $p->where('status', BookingStatus::Pending)->where('expires_at', '>', now())));
    }

    /** "Mon, Wed · 5:00 PM · 1 hour" */
    public function scheduleLabel(): string
    {
        return implode(', ', $this->days).' · '.date('g:i A', strtotime($this->start_time)).' · '.(self::DURATIONS[$this->duration_minutes] ?? $this->duration_minutes.' min');
    }

    public function classesCount(): int
    {
        return count($this->days) * $this->weeks;
    }

    /** "PKR 1,800 / hour" */
    public function rateLabel(): string
    {
        return $this->currency.' '.number_format(intdiv($this->rate_minor, 100)).' / hour';
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<ClassSession, $this> */
    public function sessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassSession::class);
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

    /** @return BelongsTo<DemoRequest, $this> */
    public function demoRequest(): BelongsTo
    {
        return $this->belongsTo(DemoRequest::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
