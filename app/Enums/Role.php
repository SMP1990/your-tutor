<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Parent = 'parent';
    case Tutor = 'tutor';
    case Admin = 'admin';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Roles a visitor may choose on the Register page (admins are created by an admin). */
    public static function selfRegistration(): array
    {
        return [self::Parent, self::Student, self::Tutor];
    }
}
