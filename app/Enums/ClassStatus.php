<?php

namespace App\Enums;

/** Attendance for one regular class (Step 4c). */
enum ClassStatus: string
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case StudentAbsent = 'student_absent';
    case TutorCancelled = 'tutor_cancelled';
    case FamilyCancelled = 'family_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Held => 'Class held',
            self::StudentAbsent => 'Student absent',
            self::TutorCancelled => 'Cancelled by tutor',
            self::FamilyCancelled => 'Cancelled by family',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::Held => 'green',
            self::StudentAbsent, self::TutorCancelled => 'red',
            self::Scheduled => 'amber',
            self::FamilyCancelled => 'grey',
        };
    }
}
