<?php

// Rules for tutor applications. Owner decisions of 24 Oct 2026; later editable from the admin panel.

return [
    // Hourly rate limits in whole PKR.
    'rate_min' => 500,
    'rate_max' => 10000,

    // Upload limits (kilobytes).
    'photo_max_kb' => 2048,
    'document_max_kb' => 5120,

    'max_subjects' => 6,
    'max_education' => 3,

    // CNIC is required for every tutor (owner decision).
    'cnic_required' => true,

    'levels' => [
        'Grade 1–5', 'Grade 6–8', 'Matric', 'FSc / ICS / I.Com', 'O Level', 'A Level', 'MDCAT', 'ECAT', 'IELTS',
    ],

    'languages' => ['Urdu', 'English', 'Punjabi', 'Sindhi', 'Pashto', 'Arabic'],

    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],

    'time_slots' => ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'],
];
