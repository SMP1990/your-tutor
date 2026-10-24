<?php

namespace Tests\Feature;

use App\Enums\DemoStatus;
use App\Models\Child;
use App\Models\DemoRequest;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\DemoRequestAnswered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Step 3b: the tutor accepts (time + Meet link) or declines a free demo request. */
class TutorDemoTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $profile;

    private User $parent;

    private DemoRequest $demo;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setTimezone('UTC')->setDateTime(2026, 10, 24, 6, 0)); // 11:00 in Pakistan

        $this->profile = TutorProfile::factory()->approved()->create();
        $this->parent = User::factory()->parent()->create();
        $this->demo = $this->makeDemo();
    }

    private function makeDemo(array $attributes = []): DemoRequest
    {
        $child = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']);

        return tap(new DemoRequest, fn ($d) => $d->forceFill($attributes + [
            'tutor_profile_id' => $this->profile->id,
            'user_id' => $this->parent->id,
            'child_id' => $child->id,
            'subject_id' => Subject::factory()->create(['name' => 'Physics'])->id,
            'student_name' => 'Ahmed',
            'grade' => 'FSc / A Level',
            'preferred_day' => 'Sat',
            'preferred_time' => 'evening',
            'phone' => '03001234567',
            'expires_at' => now()->addHours(48),
        ])->save());
    }

    private function asTutor(): static
    {
        return $this->actingAs($this->profile->user);
    }

    private function accept(array $data = [], ?DemoRequest $demo = null)
    {
        return $this->asTutor()->post(route('tutor.demos.accept', $demo ?? $this->demo), $data + [
            'scheduled_at' => '2026-10-26T17:00',
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ]);
    }

    public function test_tutor_sees_waiting_requests_and_menu_link(): void
    {
        $this->asTutor()->get('/dashboard/tutor')->assertSee('1 waiting')->assertSee(route('tutor.demos'));
        $this->asTutor()->get(route('tutor.demos'))->assertOk()
            ->assertSee('Waiting for your answer (1)')->assertSee('Ahmed')->assertSee('Physics');
    }

    public function test_accept_saves_time_in_utc_and_tells_the_family(): void
    {
        $this->accept(['tutor_note' => 'Keep the textbook ready.'])->assertRedirect(route('tutor.demos'));

        $demo = $this->demo->fresh();
        $this->assertSame(DemoStatus::Accepted, $demo->status);
        $this->assertSame('2026-10-26 12:00:00', $demo->scheduled_at->utc()->format('Y-m-d H:i:s')); // 17:00 PKT
        $this->assertSame('Mon 26 Oct, 5:00 PM', $demo->scheduledLabel());
        $this->assertSame('https://meet.google.com/abc-defg-hij', $demo->meet_link);
        Notification::assertSentTo($this->parent, DemoRequestAnswered::class);

        $this->asTutor()->get(route('tutor.demos'))->assertSee('Upcoming demos (1)')->assertSee('Open Google Meet');
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Accepted')->assertSee('Mon 26 Oct, 5:00 PM')
            ->assertSee('Keep the textbook ready.');
    }

    public function test_accept_needs_a_google_meet_link_and_a_time_in_the_next_14_days(): void
    {
        $this->accept(['meet_link' => 'https://zoom.us/j/123'])->assertSessionHasErrorsIn('demo'.$this->demo->id, 'meet_link');
        $this->accept(['scheduled_at' => '2026-10-23T17:00'])->assertSessionHasErrorsIn('demo'.$this->demo->id, 'scheduled_at');
        $this->accept(['scheduled_at' => '2026-11-20T17:00'])->assertSessionHasErrorsIn('demo'.$this->demo->id, 'scheduled_at');
        $this->assertSame(DemoStatus::Pending, $this->demo->fresh()->status);
    }

    public function test_decline_needs_a_reason_and_tells_the_family(): void
    {
        $this->asTutor()->post(route('tutor.demos.decline', $this->demo))->assertSessionHasErrorsIn('demo'.$this->demo->id, 'tutor_note');

        $this->asTutor()->post(route('tutor.demos.decline', $this->demo), ['tutor_note' => 'Fully booked this month.']);
        $this->assertSame(DemoStatus::Declined, $this->demo->fresh()->status);
        Notification::assertSentTo($this->parent, DemoRequestAnswered::class);
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Declined')->assertSee('Fully booked this month.');
    }

    public function test_tutor_cannot_answer_another_tutors_request(): void
    {
        $other = TutorProfile::factory()->approved()->create();
        $this->actingAs($other->user)->post(route('tutor.demos.accept', $this->demo), [
            'scheduled_at' => '2026-10-26T17:00', 'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertNotFound();
        $this->actingAs($this->parent)->post(route('tutor.demos.accept', $this->demo))->assertForbidden();
    }

    public function test_family_joins_only_inside_the_class_window(): void
    {
        $this->accept(); // Mon 26 Oct 17:00 PKT = 12:00 UTC
        $join = route('demos.join', $this->demo);

        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Join opens 15 min before')->assertDontSee($join);
        $this->actingAs($this->parent)->get($join)->assertRedirect(route('dashboard'))->assertSessionHas('notice');

        $this->travelTo(now()->setDateTime(2026, 10, 26, 11, 50)); // 16:50 PKT
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee($join);
        $this->actingAs($this->parent)->get($join)->assertRedirect('https://meet.google.com/abc-defg-hij');

        // Nobody else gets the link.
        $this->actingAs(User::factory()->parent()->create())->get($join)->assertNotFound();
        $this->actingAs($this->profile->user)->get($join)->assertForbidden();

        $this->travelTo(now()->setDateTime(2026, 10, 26, 13, 5)); // 18:05 PKT, window closed
        $this->actingAs($this->parent)->get($join)->assertRedirect(route('dashboard'));
    }

    public function test_tutor_marks_the_outcome_after_the_start_time(): void
    {
        $this->accept();
        $this->asTutor()->post(route('tutor.demos.finish', $this->demo), ['outcome' => 'completed'])->assertStatus(409);

        $this->travelTo(now()->setDateTime(2026, 10, 26, 12, 40));
        $this->asTutor()->get(route('tutor.demos'))->assertSee('Did these demos happen?');
        $this->asTutor()->post(route('tutor.demos.finish', $this->demo), ['outcome' => 'maybe'])->assertSessionHasErrors('outcome');
        $this->asTutor()->post(route('tutor.demos.finish', $this->demo), ['outcome' => 'completed'])->assertRedirect();

        $this->assertSame(DemoStatus::Completed, $this->demo->fresh()->status);
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Class done');
        $this->asTutor()->post(route('tutor.demos.finish', $this->demo), ['outcome' => 'no_show'])->assertStatus(409);
    }

    public function test_no_show_still_uses_the_free_demo(): void
    {
        $this->accept();
        $this->travelTo(now()->setDateTime(2026, 10, 26, 13, 0));
        $this->asTutor()->post(route('tutor.demos.finish', $this->demo), ['outcome' => 'no_show']);

        $this->assertSame(DemoStatus::NoShow, $this->demo->fresh()->status);
        $this->assertTrue(DemoRequest::active()->whereKey($this->demo->id)->exists());
    }

    public function test_expired_cancelled_or_answered_requests_cannot_be_answered(): void
    {
        $this->demo->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->accept()->assertStatus(409);

        $cancelled = $this->makeDemo(['status' => DemoStatus::Cancelled]);
        $this->accept([], $cancelled)->assertStatus(409);

        $fresh = $this->makeDemo();
        $this->accept([], $fresh)->assertRedirect();
        $this->asTutor()->post(route('tutor.demos.decline', $fresh), ['tutor_note' => 'Changed my mind'])->assertStatus(409);
    }
}
