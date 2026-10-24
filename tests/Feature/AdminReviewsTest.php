<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 5c: admins see every review and hide / show one; the tutor's rating follows. */
class AdminReviewsTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = TutorProfile::factory()->approved()->create();
    }

    private function review(int $rating, string $comment, bool $hidden = false): Review
    {
        return tap(new Review, fn ($r) => $r->forceFill([
            'tutor_profile_id' => $this->tutor->id,
            'user_id' => User::factory()->parent()->create(['name' => 'Bilal Ahmed'])->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_hidden' => $hidden,
        ])->save());
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    public function test_reviews_are_sorted_into_tabs(): void
    {
        $this->review(5, 'Excellent teacher, very clear.');
        $this->review(1, 'Rude words about the tutor here.');
        $this->review(4, 'Old hidden review text.', hidden: true);

        $admin = $this->asAdmin();
        $admin->get('/admin/reviews')->assertOk()
            ->assertSee('Low ratings (1-2 stars) (1)')->assertSee('All visible (2)')->assertSee('Hidden (1)')
            ->assertSee('Rude words about the tutor here.')->assertDontSee('Excellent teacher, very clear.')
            ->assertSee('Bilal Ahmed')->assertSee('shown as Bilal A.');
        $admin->get('/admin/reviews?tab=hidden')->assertSee('Old hidden review text.')->assertSee('Show again');
        $admin->get('/admin')->assertSee('1 low ratings')->assertSee(route('admin.reviews.index'));
    }

    public function test_hiding_removes_it_from_the_profile_and_the_rating(): void
    {
        $this->review(5, 'Excellent teacher, very clear.');
        $bad = $this->review(1, 'Rude words about the tutor here.');
        $this->tutor->refreshRating();
        $this->assertSame('3.0', number_format((float) $this->tutor->fresh()->rating_avg, 1));

        $this->asAdmin()->post(route('admin.reviews.toggle', $bad))->assertRedirect();

        $this->assertTrue($bad->fresh()->is_hidden);
        $this->assertSame(1, $this->tutor->fresh()->reviews_count);
        $this->assertSame('5.0', number_format((float) $this->tutor->fresh()->rating_avg, 1));
        $this->get('/tutors/'.$this->tutor->slug)->assertDontSee('Rude words about the tutor here.');

        $this->asAdmin()->post(route('admin.reviews.toggle', $bad)); // show again
        $this->assertFalse($bad->fresh()->is_hidden);
        $this->assertSame(2, $this->tutor->fresh()->reviews_count);
    }

    public function test_only_admins_with_two_step_login_can_moderate(): void
    {
        $r = $this->review(1, 'Rude words about the tutor here.');

        $this->get('/admin/reviews')->assertRedirect(route('login'));
        $this->actingAs($this->tutor->user)->post(route('admin.reviews.toggle', $r))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.reviews.toggle', $r))->assertRedirect(route('two-factor.show'));
        $this->assertFalse($r->fresh()->is_hidden);
    }
}
