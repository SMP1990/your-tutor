<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A problem reported by a family or a tutor (Step 6b). Created by ComplaintController only; nothing mass-assigned. */
class Complaint extends Model
{
    public const CATEGORIES = [
        'no_show' => 'The tutor or student did not join',
        'late' => 'Class started late or ended early',
        'quality' => 'Teaching quality',
        'technical' => 'Video or connection problem',
        'behaviour' => 'Rude or unsafe behaviour',
        'other' => 'Something else',
    ];

    public const MIN_DETAILS = 10;

    public const MAX_DETAILS = 1000;

    /** Most open reports one person may have on the same booking (stops spam). */
    public const MAX_OPEN_PER_BOOKING = 3;

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['status' => ComplaintStatus::class, 'replied_at' => 'datetime'];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ClassBooking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class, 'class_booking_id');
    }

    /** @return BelongsTo<ClassSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
