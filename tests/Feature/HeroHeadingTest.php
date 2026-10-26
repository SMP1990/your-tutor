<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HeroHeading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin edits the big Home page heading and its highlighted words. */
class HeroHeadingTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    private function payload(array $override = []): array
    {
        $stats = [];
        foreach (['students', 'tutors', 'subjects', 'rating'] as $stat) {
            $stats[$stat] = ['mode' => 'manual', 'value' => ['students' => '2K+', 'tutors' => '40+', 'subjects' => '12', 'rating' => '4.6'][$stat]];
        }

        return $override + ['whatsapp' => '', 'support_email' => '', 'support_hours' => '', 'stats' => $stats];
    }

    public function test_home_shows_the_original_heading_until_the_admin_changes_it(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<h1>Find a <span class="hero__mark">trusted tutor</span> for every subject, online.</h1>', false);
    }

    public function test_the_admin_sets_a_new_heading_with_highlighted_words(): void
    {
        $this->asAdmin()->put('/admin/settings', $this->payload([
            'hero_title' => 'Learn  anything with a Great Tutor',
            'hero_mark' => 'great tutor',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $this->get('/')->assertSee('<h1>Learn anything with a <span class="hero__mark">Great Tutor</span></h1>', false)
            ->assertDontSee('every subject, online');
        $this->asAdmin()->get('/admin/settings')->assertSee('Home page heading')->assertSee('Learn anything with a Great Tutor');
    }

    public function test_a_heading_without_highlighted_words_has_no_highlight(): void
    {
        $this->asAdmin()->put('/admin/settings', $this->payload(['hero_title' => 'Good teachers, close to home']))->assertSessionHasNoErrors();

        $this->get('/')->assertSee('<h1>Good teachers, close to home</h1>', false);
    }

    public function test_the_highlighted_words_must_be_part_of_the_heading(): void
    {
        $admin = $this->asAdmin();

        $admin->put('/admin/settings', $this->payload(['hero_title' => 'Good teachers', 'hero_mark' => 'brilliant']))
            ->assertSessionHasErrors('hero_mark');
        $admin->put('/admin/settings', $this->payload(['hero_title' => '', 'hero_mark' => 'brilliant']))
            ->assertSessionHasErrors('hero_mark');
        $admin->put('/admin/settings', $this->payload(['hero_title' => str_repeat('a', HeroHeading::TITLE_MAX + 1)]))
            ->assertSessionHasErrors('hero_title');
        $this->get('/')->assertSee('trusted tutor');
    }

    public function test_the_heading_is_escaped_and_clearing_it_brings_the_original_back(): void
    {
        $admin = $this->asAdmin();
        $admin->put('/admin/settings', $this->payload(['hero_title' => '<script>alert(1)</script> Tutors']))->assertSessionHasNoErrors();

        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; Tutors', $html);

        $admin->put('/admin/settings', $this->payload())->assertRedirect();

        $this->get('/')->assertSee('<span class="hero__mark">trusted tutor</span>', false);
    }

    public function test_the_page_title_and_meta_keep_their_own_wording(): void
    {
        $this->asAdmin()->put('/admin/settings', $this->payload(['hero_title' => 'Brand new heading']))->assertSessionHasNoErrors();

        $this->get('/')->assertSee('<title>'.config('app.name').' | Find a trusted tutor online</title>', false);
    }
}
