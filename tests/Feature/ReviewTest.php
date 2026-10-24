<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Models\Child;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Review;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 5a: a family reviews a tutor after a held class; the tutor's rating comes from real reviews. */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    private User $parent;

    private ClassBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = TutorProfile::factory()->approved()->create();
        $this->parent = User::factory()->parent()->create(['name' => 'Sadia Malik']);
        $this->booking = $this->makeBooking(Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']));
    }

    private function makeBooking(Child $child, ClassStatus $firstClass = ClassStatus::Held): ClassBooking
    {
        $booking = tap(new ClassBooking, fn ($b) => $b->forceFill([
            'tutor_profile_id' => $this->tutor->id,
            'user_id' => $this->parent->id,
            'child_id' => $child->id,
            'student_name' => $child->name,
            'grade' => 'O Level',
            'days' => ['Mon'],
            'start_time' => '17:00',
            'duration_minutes' => 60,
            'starts_on' => now()->subWeek()->toDateString(),
            'weeks' => 4,
            'rate_minor' => 180000,
            'currency' => 'PKR',
            'status' => BookingStatus::Accepted,
            'meet_link' => 'https://meet.google.com/a-b-c',
            'expires_at' => now(),
        ])->save());
        $session = ClassSession::create(['class_booking_id' => $booking->id, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHour()]);
        $session->forceFill(['status' => $firstClass])->save();

        return $booking;
    }

    private function review(array $data = [], ?ClassBooking $booking = null)
    {
        return $this->actingAs($this->parent)->put(route('reviews.update', $booking ?? $this->booking), $data + [
            'rating' => 5,
            'comment' => 'Explains every step clearly. Marks went up.',
        ]);
    }

    public function test_family_sees_rate_button_after_a_held_class_and_posts_a_review(): void
    {
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Rate your tutor');
        $this->actingAs($this->parent)->get(route('reviews.edit', $this->booking))->assertOk()->assertSee('Rate your tutor');

        $this->review()->assertRedirect(route('dashboard'))->assertSessionHas('notice');

        $r = Review::sole();
        $this->assertSame(5, $r->rating);
        $this->assertSame('Sadia M.', $r->reviewerName());
        $this->assertSame('5.0', number_format((float) $this->tutor->fresh()->rating_avg, 1));
        $this->assertSame(1, $this->tutor->fresh()->reviews_count);
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Edit your review');
    }

    public function test_saving_again_edits_the_same_review(): void
    {
        $this->review();
        $this->review(['rating' => 3, 'comment' => 'Good, but sometimes late.']);

        $this->assertSame(1, Review::count());
        $this->assertSame(3, Review::sole()->rating);
        $this->assertSame('3.0', number_format((float) $this->tutor->fresh()->rating_avg, 1));
    }

    public function test_rating_is_the_average_of_visible_reviews(): void
    {
        $this->review(['rating' => 5]);
        $sara = $this->makeBooking(Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Sara']));
        $this->review(['rating' => 4], $sara); // second child: its own review

        $this->assertSame(2, $this->tutor->fresh()->reviews_count);
        $this->assertSame('4.5', number_format((float) $this->tutor->fresh()->rating_avg, 1));

        Review::where('rating', 5)->update(['is_hidden' => true]);
        $this->tutor->refreshRating();
        $this->assertSame(1, $this->tutor->fresh()->reviews_count);
        $this->assertSame('4.0', number_format((float) $this->tutor->fresh()->rating_avg, 1));
    }

    // ---- Step 5b: reviews on the profile and in the tutor's dashboard ----

    public function test_profile_lists_visible_reviews_with_privacy_names(): void
    {
        $this->review(['comment' => 'Explains every step clearly. Marks went up.']);
        tap(Review::sole()->replicate(), fn ($r) => $r->forceFill(['comment' => 'This one is hidden by admin', 'is_hidden' => true])->save());

        $this->get('/tutors/'.$this->tutor->slug)->assertOk()
            ->assertSee('Explains every step clearly. Marks went up.')
            ->assertSee('Sadia M.')->assertDontSee('Sadia Malik')
            ->assertSee('Parent')->assertSee('(1 review)')
            ->assertDontSee('This one is hidden by admin');
    }

    public function test_tutor_sees_their_reviews_in_the_dashboard(): void
    {
        $this->actingAs($this->tutor->user)->get(route('tutor.reviews'))->assertOk()->assertSee('No reviews yet');

        $this->review();
        $this->actingAs($this->tutor->user)->get(route('tutor.reviews'))
            ->assertSee('★ 5.0 from 1 review')->assertSee('Explains every step clearly.')->assertSee('Sadia M.');
        $this->actingAs($this->parent)->get(route('tutor.reviews'))->assertForbidden();
    }

    public function test_no_review_before_a_class_was_held(): void
    {
        $booking = $this->makeBooking(Child::factory()->create(['parent_id' => $this->parent->id]), ClassStatus::StudentAbsent);

        $this->actingAs($this->parent)->get(route('reviews.edit', $booking))->assertStatus(409);
        $this->review([], $booking)->assertStatus(409);
    }

    public function test_only_the_family_can_review_and_input_is_checked(): void
    {
        $this->actingAs(User::factory()->parent()->create())->get(route('reviews.edit', $this->booking))->assertNotFound();
        $this->actingAs($this->tutor->user)->get(route('reviews.edit', $this->booking))->assertForbidden();

        $this->review(['rating' => 6, 'comment' => 'short'])->assertSessionHasErrors(['rating', 'comment']);
        $this->assertSame(0, Review::count());
    }
}
