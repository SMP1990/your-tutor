<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One thing an admin did to an account (Step 6a), kept as a record. Written by code only. */
class AdminAction extends Model
{
    public const SUSPENDED = 'suspended';

    public const UNSUSPENDED = 'unsuspended';

    public const EMAIL_VERIFIED = 'email_verified';

    public const LABELS = [
        self::SUSPENDED => 'Account suspended',
        self::UNSUSPENDED => 'Account active again',
        self::EMAIL_VERIFIED => 'Email marked as verified',
    ];

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
