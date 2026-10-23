<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    public function test_dashboards_load_with_preview_notice(): void
    {
        $pages = [
            '/student' => 'Hi Ahmed',
            '/parent' => 'Good evening, Sadia',
            '/tutor' => 'Welcome, Ayesha',
            '/admin' => 'Admin overview',
        ];

        foreach ($pages as $url => $heading) {
            $this->get($url)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Preview with sample data')
                ->assertSee('<meta name="robots" content="noindex">', false);
        }
    }

    public function test_admin_shows_site_figures_from_config(): void
    {
        config(['pricing.package_discount_percent' => 15]);

        $this->get('/admin')->assertSee('Monthly package discount')->assertSee('value="15"', false);
    }
}
