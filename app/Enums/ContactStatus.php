<?php

namespace App\Enums;

/** Where a Contact-form message stands in the admin inbox (Step 6c). */
enum ContactStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Answered = 'answered';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function colour(): string
    {
        return match ($this) {
            self::New => 'red',
            self::Read => 'amber',
            self::Answered => 'green',
        };
    }
}
