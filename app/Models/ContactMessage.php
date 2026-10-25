<?php

namespace App\Models;

use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A message from the public Contact form (Step 6c). Created by ContactController only; nothing mass-assigned. */
class ContactMessage extends Model
{
    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['status' => ContactStatus::class];
    }

    /** wa.me link for the sender's Pakistani mobile (03001234567 or +923001234567), or null without a phone. */
    public function whatsappUrl(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $digits = ltrim($this->phone, '+');
        $digits = str_starts_with($digits, '0') ? '92'.substr($digits, 1) : $digits;

        return 'https://wa.me/'.$digits;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
