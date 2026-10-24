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
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for tutor',
            self::Completed => 'Class done',
            self::NoShow => 'Student did not join',
            default => ucfirst($this->value),
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Accepted, self::Completed => 'green',
            self::Pending => 'amber',
            self::Declined, self::NoShow => 'red',
            default => 'grey',
        };
    }
}
