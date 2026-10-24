<?php

namespace Tests\Feature;

use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    use SeedsDemoContent;

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
            ->assertSee(route('pricing'))
            ->assertSee(route('become-a-tutor'));
    }

    public function test_become_a_tutor_page_loads_with_earnings_example(): void
    {
        config(['pricing.tutor_example' => ['rate' => 2000, 'classes_per_day' => 2, 'days_per_week' => 5]]);

        $this->get('/become-a-tutor')
            ->assertOk()
            ->assertSee('Teach online. Earn from home.')
            ->assertSee('PKR 80,000')       // 2,000 x 2 a day x 5 days x 4 weeks
            ->assertSee('Four steps to your first student');
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
