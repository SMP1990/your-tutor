<?php

namespace Tests\Feature;

use App\Support\DemoSubjects;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class SubjectPagesTest extends TestCase
{
    use SeedsDemoContent;

    public function test_subjects_index_lists_every_subject(): void
    {
        $response = $this->get('/subjects')->assertOk();

        foreach (DemoSubjects::all() as $subject) {
            $response->assertSee(route('subjects.show', $subject['slug']));
        }
    }

    public function test_every_subject_page_loads(): void
    {
        foreach (DemoSubjects::all() as $subject) {
            $this->get(route('subjects.show', $subject['slug']))
                ->assertOk()
                ->assertSee('Online ' . $subject['name'] . ' tutors');
        }
    }

    public function test_physics_page_shows_physics_tutors(): void
    {
        $this->get('/subjects/physics')
            ->assertSee('Ayesha R.')
            ->assertSee('Zainab H.')
            ->assertDontSee('Mehwish T.');   // teaches Biology and Chemistry only
    }

    public function test_entry_tests_page_matches_tutors_by_level(): void
    {
        $this->get('/subjects/entry-tests')->assertSee('Mehwish T.')->assertSee('Omar A.');
    }

    public function test_subject_without_tutors_shows_request_option(): void
    {
        $this->get('/subjects/urdu')->assertSee('Tutors coming soon')->assertSee('Request a tutor');
    }

    public function test_home_and_header_link_to_subject_pages(): void
    {
        $this->get('/')
            ->assertSee(route('subjects.index'))
            ->assertSee(route('subjects.show', 'physics'));
    }

    public function test_unknown_subject_returns_404(): void
    {
        $this->get('/subjects/astrology')->assertNotFound();
    }
}
