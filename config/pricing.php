<?php

// DUMMY figures until the owner decides fees (plan step A3). Moves to the admin panel later.
// Amounts are whole PKR here for display only; payments will store minor units.

return [
    'currency' => 'PKR',
    'demo_minutes' => 30,
    'rate_range' => [1000, 3000],          // typical tutor hourly rates
    'package_discount_percent' => 10,      // monthly package vs pay per session
    'sibling_discount_percent' => 10,      // second child onwards
    'package_sizes' => [8, 12, 20],        // classes per month
    'example_rate' => 1800,                // used in the worked example

    // Become a Tutor earnings example (before the platform fee, which is not decided yet)
    'tutor_example' => ['rate' => 1800, 'classes_per_day' => 3, 'days_per_week' => 5],
    'payout_methods' => ['Bank account', 'JazzCash', 'Easypaisa'],
];
