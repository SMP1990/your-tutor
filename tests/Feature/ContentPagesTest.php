<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    public function test_how_it_works_page_loads(): void
    {
        $this->get('/how-it-works')
            ->assertOk()
            ->assertSee('Online tutoring, step by step')
            ->assertSee('For tutors')
            ->assertSee('Common questions');
    }

    public function test_header_links_to_content_pages(): void
    {
        $this->get('/')
            ->assertSee(route('how-it-works'))
            ->assertSee(route('pricing'));
    }

    public function test_pricing_page_uses_configured_figures(): void
    {
        config(['pricing.example_rate' => 2000, 'pricing.package_discount_percent' => 10]);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Pay only for classes that happen')
            ->assertSee('PKR 16,000')   // 8 classes at 2,000 per session
            ->assertSee('PKR 14,400');  // same with 10% package discount
    }
}
