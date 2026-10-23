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

    public function test_header_links_to_how_it_works(): void
    {
        $this->get('/')->assertSee(route('how-it-works'));
    }
}
