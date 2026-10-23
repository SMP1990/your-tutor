<?php

namespace Tests\Feature;

use Tests\TestCase;

class TutorPagesTest extends TestCase
{
    public function test_home_page_loads_with_featured_tutors(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ayesha R.')
            ->assertSee(route('tutors.show', 'ayesha-r'));
    }

    public function test_find_a_tutor_page_links_to_profiles(): void
    {
        $this->get('/tutors')
            ->assertOk()
            ->assertSee(route('tutors.show', 'usman-f'));
    }

    public function test_tutor_profile_page_shows_tutor_details(): void
    {
        $this->get('/tutors/ayesha-r')
            ->assertOk()
            ->assertSee('Ayesha R.')
            ->assertSee('MSc Physics')
            ->assertSee('PKR 1,800')
            ->assertSee('Book free demo')
            ->assertSee('Similar tutors');
    }

    public function test_unknown_tutor_returns_404(): void
    {
        $this->get('/tutors/nobody')->assertNotFound();
    }
}
