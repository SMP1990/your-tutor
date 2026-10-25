<?php

namespace App\Enums;

/** Where a complaint stands (Step 6b). */
enum ComplaintStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Closed => 'Closed',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::New => 'red',
            self::InProgress => 'amber',
            self::Closed => 'green',
        };
    }
}
