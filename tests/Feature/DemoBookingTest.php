<?php

namespace Tests\Feature;

use App\Enums\DemoStatus;
use App\Models\Child;
use App\Models\DemoRequest;
use App\Models\TutorProfile;
use App\Models\User;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class DemoBookingTest extends TestCase
{
    use SeedsDemoContent;

    private User $parent;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = User::factory()->parent()->create(['name' => 'Sadia Malik', 'phone' => '03001234567']);
        $this->child = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']);
    }

    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'child_id' => $this->child->id,
            'phone' => '0300 123-4567',
            'subject' => 'physics',
            'grade' => 'FSc / A Level',
            'day' => 'Sat',
            'time' => 'morning',
            'contact_ok' => '1',
        ];
    }

    private function book(array $overrides = [], string $tutor = 'ayesha-r', ?User $as = null)
    {
        return $this->actingAs($as ?? $this->parent)->post("/tutors/{$tutor}/demo", $this->validData($overrides));
    }

    public function test_guests_see_the_page_and_are_asked_to_log_in(): void
    {
        $this->get('/tutors/ayesha-r/demo')->assertOk()
            ->assertSee('Book a free demo class')->assertSee('Ayesha R.')
            ->assertSee('Create free account')->assertDontSee('Request free demo');
        $this->assertSame(route('tutors.demo', 'ayesha-r'), session('url.intended'));

        $this->post('/tutors/ayesha-r/demo', $this->validData())->assertRedirect(route('login'));
        $this->assertSame(0, DemoRequest::count());
    }

    public function test_profile_and_cards_link_to_the_demo_form(): void
    {
        $this->get('/tutors/ayesha-r')->assertSee(route('tutors.demo', 'ayesha-r'));
        $this->get('/tutors')->assertSee(route('tutors.demo', 'usman-f'));
    }

    public function test_parent_books_for_their_child_and_it_is_saved(): void
    {
        $this->actingAs($this->parent)->get('/tutors/ayesha-r/demo')->assertSee('Ahmed')->assertSee('Request free demo');

        $this->book()->assertRedirect(route('tutors.demo.sent', 'ayesha-r'));

        $demo = DemoRequest::sole();
        $this->assertSame(TutorProfile::where('slug', 'ayesha-r')->value('id'), $demo->tutor_profile_id);
        $this->assertSame($this->child->id, $demo->child_id);
        $this->assertSame('Ahmed', $demo->student_name);
        $this->assertSame('physics', $demo->subject->slug);
        $this->assertSame('03001234567', $demo->phone);
        $this->assertSame(DemoStatus::Pending, $demo->status);
        $this->assertTrue($demo->expires_at->between(now()->addHours(47), now()->addHours(49)));

        $this->get(route('tutors.demo.sent', 'ayesha-r'))->assertOk()->assertSee('Request sent');
        $this->get('/dashboard/parent')->assertSee('Free demo requests')->assertSee('Ayesha R.')->assertSee('Waiting for tutor');
    }

    public function test_student_books_for_themself(): void
    {
        $student = User::factory()->create(['name' => 'Ali Khan']);
        $this->book(['child_id' => null], 'ayesha-r', $student)->assertSessionHasNoErrors();

        $this->assertSame('Ali Khan', DemoRequest::sole()->student_name);
        $this->assertNull(DemoRequest::sole()->child_id);
        $this->get('/dashboard/student')->assertSee('Ayesha R.');
    }

    public function test_parent_cannot_book_for_someone_elses_child(): void
    {
        $other = Child::factory()->create();
        $this->book(['child_id' => $other->id])->assertSessionHasErrors('child_id');
        $this->assertSame(0, DemoRequest::count());
    }

    public function test_tutors_and_admins_cannot_book(): void
    {
        $tutor = User::factory()->tutor()->create();
        $this->actingAs($tutor)->get('/tutors/ayesha-r/demo')->assertSee('booked from a parent or student account');
        $this->book([], 'ayesha-r', $tutor)->assertForbidden();
    }

    public function test_parent_without_children_is_asked_to_add_one(): void
    {
        $parent = User::factory()->parent()->create();
        $this->actingAs($parent)->get('/tutors/ayesha-r/demo')->assertSee('First add the child')->assertDontSee('Request free demo');
    }

    public function test_invalid_request_shows_errors(): void
    {
        $this->book([
            'phone' => '12345',
            'subject' => 'chemistry',   // Ayesha does not teach it
            'day' => 'Fri',             // Ayesha is not free on Friday
            'contact_ok' => '',
        ])->assertSessionHasErrors(['phone', 'subject', 'day', 'contact_ok']);
    }

    public function test_one_free_demo_per_tutor_per_child(): void
    {
        $this->book()->assertSessionHasNoErrors();
        $this->book()->assertSessionHasErrors('tutor');

        $sara = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Sara']);
        $this->book(['child_id' => $sara->id])->assertSessionHasNoErrors();
        $this->assertSame(2, DemoRequest::count());
    }

    public function test_a_declined_or_expired_request_can_be_sent_again(): void
    {
        $this->book();
        DemoRequest::sole()->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->assertSame(DemoStatus::Expired, DemoRequest::sole()->currentStatus());

        $this->book()->assertSessionHasNoErrors();
        $this->assertSame(2, DemoRequest::count());
    }

    public function test_at_most_three_requests_wait_at_once(): void
    {
        foreach (['ayesha-r', 'usman-f', 'bilal-k'] as $slug) {
            $this->book([], $slug)->assertSessionHasNoErrors();
        }
        $this->book([], 'zainab-h')->assertSessionHasErrors('tutor');
    }

    public function test_parent_can_cancel_a_waiting_request_but_not_someone_elses(): void
    {
        $this->book();
        $demo = DemoRequest::sole();

        $this->actingAs(User::factory()->parent()->create())->post(route('demos.cancel', $demo))->assertNotFound();
        $this->actingAs($this->parent)->post(route('demos.cancel', $demo))->assertRedirect();
        $this->assertSame(DemoStatus::Cancelled, $demo->fresh()->status);
        $this->actingAs($this->parent)->post(route('demos.cancel', $demo))->assertStatus(409);

        $this->book()->assertSessionHasNoErrors(); // a cancelled request frees the tutor again
    }

    public function test_unknown_tutor_demo_returns_404(): void
    {
        $this->get('/tutors/nobody/demo')->assertNotFound();
    }
}
