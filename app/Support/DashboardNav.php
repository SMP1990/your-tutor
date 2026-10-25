<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Sidebar menus for the dashboards. An item links to its route once that page exists; until then it
 * shows as "soon". The preview dashboards (sample data) pass $live = false: no links, first item active.
 */
class DashboardNav
{
    private const ICONS = [
        'home' => '<path d="m3 11 9-8 9 8M5 10v10h14V10"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'notes' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'wallet' => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20M16 15h2"/>',
        'star' => '<path d="m12 2 3 6.5 7 .8-5.2 4.8 1.5 7L12 17.6 5.7 21.1l1.5-7L2 9.3l7-.8z"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 11a3 3 0 1 0 0-6M22 21a6 6 0 0 0-4-5.6"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/>',
        'refund' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'flag' => '<path d="M4 22V4M4 4h13l-2 4 2 4H4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
    ];

    /** label => [icon, route name (null = not built yet), routes that make it active] */
    private const MENUS = [
        'student' => [
            'Overview' => ['home', 'dashboard.student'], 'My classes' => ['calendar', 'classes.index', 'classes.*'], 'Class notes' => ['notes', null],
            'Payments' => ['wallet', null], 'Reviews' => ['star', null], 'My reports' => ['flag', 'complaints.index', 'complaints.*'], 'Profile' => ['user', null],
        ],
        'parent' => [
            'Overview' => ['home', 'dashboard.parent'], 'Children' => ['users', 'children.create', 'children.*'],
            'Classes' => ['calendar', 'classes.index', 'classes.*'], 'Progress' => ['chart', null], 'Payments' => ['wallet', null], 'My reports' => ['flag', 'complaints.index', 'complaints.*'], 'Profile' => ['user', null],
        ],
        'tutor' => [
            'Overview' => ['home', 'dashboard.tutor'], 'Application' => ['check', 'tutor.application', 'tutor.application*'],
            'Demo requests' => ['calendar', 'tutor.demos', 'tutor.demos*'],
            'Class bookings' => ['notes', 'tutor.bookings', 'tutor.bookings*'], 'Timetable' => ['clock', 'classes.index', 'classes.*'], 'Earnings' => ['wallet', null], 'Reviews' => ['star', 'tutor.reviews'], 'My reports' => ['flag', 'complaints.index', 'complaints.*'],
        ],
        'admin' => [
            'Overview' => ['home', 'dashboard.admin'], 'Tutor approvals' => ['check', 'admin.tutors.index', 'admin.tutors.*'],
            'Demo requests' => ['clock', 'admin.demos.index', 'admin.demos.*'],
            'Bookings' => ['notes', 'admin.bookings.index', 'admin.bookings.*'],
            'Classes' => ['calendar', 'admin.classes.index', 'admin.classes.*'],
            'Reviews' => ['star', 'admin.reviews.index', 'admin.reviews.*'],
            'Users' => ['users', 'admin.users.index', 'admin.users.*'], 'Payments' => ['wallet', null],
            'Refunds' => ['refund', null], 'Complaints' => ['flag', 'admin.complaints.index', 'admin.complaints.*'],
            'Messages' => ['notes', 'admin.contact.index', 'admin.contact.*'], 'Site settings' => ['settings', null],
        ],
    ];

    public static function for(string $dashboard, bool $live = true): array
    {
        $items = [];
        foreach (self::MENUS[$dashboard] as $label => $entry) {
            [$icon, $route] = $entry;
            $pattern = $entry[2] ?? $route;
            $items[] = [
                'label' => $label,
                'icon' => self::ICONS[$icon],
                'href' => $live && $route ? route($route) : null,
                'active' => $live ? ($pattern && request()->routeIs($pattern)) : $items === [],
                'soon' => $live && ! $route,
            ];
        }

        return $items;
    }
}
