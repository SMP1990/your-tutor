<?php

namespace App\Enums;

/** Where a free demo request stands. "Pending" past its expiry time counts as Expired. */
enum DemoStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for tutor',
            default => ucfirst($this->value),
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Accepted, self::Completed => 'green',
            self::Pending => 'amber',
            self::Declined => 'red',
            default => 'grey',
        };
    }
}
