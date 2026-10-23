<?php

namespace App\Http\Controllers;

use App\Support\DashboardNav;

/** DESIGN ONLY: dashboards with sample data, no login. Real data comes with the database step. */
class DashboardPreviewController extends Controller
{
    private const USERS = [
        'student' => ['name' => 'Ahmed Malik', 'role' => 'Student'],
        'parent' => ['name' => 'Sadia Malik', 'role' => 'Parent'],
        'tutor' => ['name' => 'Ayesha Rehman', 'role' => 'Tutor'],
        'admin' => ['name' => 'Admin', 'role' => 'Admin'],
    ];

    public function show(string $dashboard)
    {
        return view("dashboards.{$dashboard}", [
            'nav' => DashboardNav::for($dashboard),
            'user' => self::USERS[$dashboard],
        ]);
    }
}
