<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class TutorSearchTest extends TestCase
{
    use SeedsDemoContent;

    public function test_lists_all_approved_tutors_with_a_real_count(): void
    {
        $this->get('/tutors')->assertOk()->assertSee('8 tutors match your search')
            ->assertSee('Ayesha R.')->assertSee('Zainab H.');
    }

    public function test_tutors_not_approved_are_never_listed(): void
    {
        $draft = TutorProfile::factory()->create();
        $pending = TutorProfile::factory()->create(['status' => 'submitted']);

        $this->get('/tutors')->assertSee('8 tutors')
            ->assertDontSee($draft->user->name)->assertDontSee($pending->user->name);
        $this->get('/tutors/'.$draft->slug)->assertNotFound();
        $this->get('/tutors/'.$draft->slug.'/demo')->assertNotFound();
    }

    public function test_filters_by_subject_level_price_and_rating(): void
    {
        $this->get('/tutors?subject[]=computer-science')->assertSee('2 tutors')
            ->assertSee('Hamza S.')->assertSee('Omar A.')->assertDontSee('Ayesha R.');

        $this->get('/tutors?level=MDCAT')->assertSee('Mehwish T.')->assertDontSee('Hamza S.');
        $this->get('/tutors?max=1500')->assertSee('Bilal K.')->assertSee('Sana N.')->assertDontSee('Zainab H.');
        $this->get('/tutors?min=2500')->assertSee('Zainab H.')->assertDontSee('Bilal K.');
        $this->get('/tutors?q=hamza')->assertSee('1 tutor match')->assertSee('Hamza S.');
    }

    public function test_sorts_by_price(): void
    {
        $this->get('/tutors?sort=price-asc')->assertSeeInOrder(['Bilal K.', 'Sana N.', 'Zainab H.']);
        $this->get('/tutors?sort=price-desc')->assertSeeInOrder(['Zainab H.', 'Usman F.', 'Bilal K.']);
    }

    public function test_active_filters_show_as_removable_chips(): void
    {
        $this->get('/tutors?subject[]=physics&level=FSc+%2F+ICS+%2F+I.Com')
            ->assertSee('Remove filter Physics')->assertSee('Remove filter FSc / ICS / I.Com')
            ->assertSee(e(route('tutors.index', ['level' => 'FSc / ICS / I.Com'])), false);
    }

    public function test_bad_filter_values_are_ignored_not_errors(): void
    {
        $this->get('/tutors?sort=drop&rating=9&level=nope&min=abc&subject=physics')->assertOk()->assertSee('6 tutors');
    }

    public function test_empty_search_offers_a_way_out(): void
    {
        $this->get('/tutors?q=nobody-teaches-this')->assertOk()->assertSee('0 tutors')->assertSee('Request a tutor');
    }

    public function test_results_are_paged_and_keep_the_filters(): void
    {
        $physics = Subject::where('slug', 'physics')->first();
        TutorProfile::factory()->approved()->count(12)->create()->each(fn ($p) => $p->subjects()->attach($physics));

        $this->get('/tutors?subject[]=physics')->assertSee('18 tutors')->assertSee('Page 1 of 2')->assertSee('page=2', false);
        $this->get('/tutors?subject[]=physics&page=2')->assertOk()->assertSee('subject%5B0%5D=physics', false);
    }

    public function test_a_newly_approved_tutor_appears_as_new_without_fake_reviews(): void
    {
        $profile = TutorProfile::factory()->approved()->create();
        $profile->subjects()->attach(Subject::where('slug', 'urdu')->first());

        $this->get('/subjects/urdu')->assertSee($profile->user->name)->assertSee('New tutor');
        $this->get('/tutors/'.$profile->slug)->assertOk()->assertSee('No reviews yet')->assertDontSee('Rabia S.');
    }

    public function test_demo_remove_deletes_only_sample_tutors(): void
    {
        $real = TutorProfile::factory()->approved()->create();

        $this->artisan('demo:remove', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, TutorProfile::count());
        $this->assertTrue(User::whereKey($real->user_id)->exists());
        $this->get('/tutors/ayesha-r')->assertNotFound();
        $this->get('/tutors')->assertSee('1 tutor match');
    }
}
