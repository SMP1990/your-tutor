<?php

namespace App\Enums;

/** Where a regular-classes booking stands. "Pending" past its expiry time counts as Expired. */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for tutor',
            self::Accepted => 'Confirmed',
            default => ucfirst($this->value),
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Accepted => 'green',
            self::Pending => 'amber',
            self::Declined => 'red',
            default => 'grey',
        };
    }
}
