<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    public function test_dashboards_load_with_preview_notice(): void
    {
        $pages = [
            '/preview/student' => 'Hi Ahmed',
            '/preview/parent' => 'Good evening, Sadia',
            '/preview/tutor' => 'Welcome, Ayesha',
            '/preview/admin' => 'Admin overview',
        ];

        foreach ($pages as $url => $heading) {
            $this->get($url)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Preview with sample data')
                ->assertSee('<meta name="robots" content="noindex">', false);
        }
    }

    public function test_login_page_has_temporary_links_to_dashboards(): void
    {
        $response = $this->get('/login')->assertSee('Preview only');

        foreach (['student', 'parent', 'tutor', 'admin'] as $dashboard) {
            $response->assertSee(route("preview.{$dashboard}"));
        }
    }

    public function test_admin_shows_site_figures_from_config(): void
    {
        config(['pricing.package_discount_percent' => 15]);

        $this->get('/preview/admin')->assertSee('Monthly package discount')->assertSee('value="15"', false);
    }
}
