<?php

namespace App\Enums;

/** Where a tutor's application stands. Only Approved tutors are shown publicly. */
enum TutorStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case DocumentsNeeded = 'documents_needed';
    case InterviewScheduled = 'interview_scheduled';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
