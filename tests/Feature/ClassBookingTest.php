<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DemoStatus;
use App\Models\Child;
use App\Models\ClassBooking;
use App\Models\DemoRequest;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\BookingAnswered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Step 4a: after a completed demo the family books regular weekly classes; the tutor confirms or declines. */
class ClassBookingTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $profile;

    private User $parent;

    private DemoRequest $demo;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setTimezone('UTC')->setDateTime(2026, 10, 24, 6, 0));

        $this->profile = TutorProfile::factory()->approved()->create([
            'hourly_rate_minor' => 180000,
            'availability' => ['Mon' => ['evening'], 'Wed' => ['evening'], 'Sat' => ['morning']],
        ]);
        $this->parent = User::factory()->parent()->create();
        $this->demo = $this->makeDemo(DemoStatus::Completed);
    }

    private function makeDemo(DemoStatus $status): DemoRequest
    {
        $child = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']);

        return tap(new DemoRequest, fn ($d) => $d->forceFill([
            'tutor_profile_id' => $this->profile->id,
            'user_id' => $this->parent->id,
            'child_id' => $child->id,
            'subject_id' => Subject::factory()->create(['name' => 'Physics'])->id,
            'student_name' => 'Ahmed',
            'grade' => 'FSc / A Level',
            'preferred_day' => 'Sat',
            'preferred_time' => 'morning',
            'phone' => '03001234567',
            'status' => $status,
            'expires_at' => now()->addHours(48),
        ])->save());
    }

    private function book(array $overrides = [], ?DemoRequest $demo = null)
    {
        return $this->actingAs($this->parent)->post(route('bookings.store', $demo ?? $this->demo), $overrides + [
            'days' => ['Wed', 'Mon'],
            'start_time' => '17:00',
            'duration' => 60,
            'starts_on' => '2026-10-26',
            'weeks' => 4,
            'grade' => 'FSc / A Level',
        ]);
    }

    public function test_completed_demo_offers_booking_and_the_request_is_saved(): void
    {
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee(route('bookings.create', $this->demo));
        $this->actingAs($this->parent)->get(route('bookings.create', $this->demo))->assertOk()->assertSee('Book regular classes')->assertSee('PKR 1,800');

        $this->book(['notes' => 'Board exams in March'])->assertRedirect(route('dashboard'))->assertSessionHas('notice');

        $b = ClassBooking::sole();
        $this->assertSame(['Mon', 'Wed'], $b->days); // week order
        $this->assertSame(8, $b->classesCount());
        $this->assertSame(180000, $b->rate_minor);
        $this->assertSame('Ahmed', $b->student_name);
        $this->assertSame($this->demo->child_id, $b->child_id);
        $this->assertSame(BookingStatus::Pending, $b->status);
        $this->assertSame('Mon, Wed · 5:00 PM · 1 hour', $b->scheduleLabel());

        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Regular classes')->assertSee('Waiting for tutor')
            ->assertDontSee(route('bookings.create', $this->demo));
    }

    public function test_booking_needs_a_completed_demo_and_only_the_family_can_book(): void
    {
        $pending = $this->makeDemo(DemoStatus::Accepted);
        $this->actingAs($this->parent)->get(route('bookings.create', $pending))->assertStatus(409);
        $this->book([], $pending)->assertStatus(409);

        $this->actingAs(User::factory()->parent()->create())->get(route('bookings.create', $this->demo))->assertNotFound();
        $this->actingAs($this->profile->user)->get(route('bookings.create', $this->demo))->assertForbidden();
    }

    public function test_one_open_booking_per_tutor_per_learner(): void
    {
        $this->book()->assertSessionHasNoErrors();
        $this->book()->assertStatus(409);
    }

    public function test_invalid_choices_are_rejected(): void
    {
        $this->book([
            'days' => ['Fri'],          // tutor not free on Friday
            'start_time' => '03:00',
            'duration' => 20,
            'starts_on' => '2026-12-25', // more than 30 days ahead
            'weeks' => 5,
        ])->assertSessionHasErrors(['days.0', 'start_time', 'duration', 'starts_on', 'weeks']);
        $this->book(['starts_on' => '2026-10-24'])->assertSessionHasErrors('starts_on'); // today
        $this->assertSame(0, ClassBooking::count());
    }

    public function test_family_can_cancel_while_waiting(): void
    {
        $this->book();
        $b = ClassBooking::sole();

        $this->actingAs($this->parent)->post(route('bookings.cancel', $b))->assertRedirect();
        $this->assertSame(BookingStatus::Cancelled, $b->fresh()->status);
        $this->actingAs($this->parent)->get(route('bookings.create', $this->demo))->assertOk(); // can book again
    }

    public function test_tutor_confirms_with_a_meet_link_and_family_is_told(): void
    {
        $this->book();
        $b = ClassBooking::sole();
        $tutor = $this->actingAs($this->profile->user);

        $tutor->get('/dashboard/tutor')->assertSee('Regular class bookings')->assertSee('1 waiting');
        $tutor->get(route('tutor.bookings'))->assertOk()->assertSee('Waiting for your answer (1)')->assertSee('8 classes');

        $tutor->post(route('tutor.bookings.accept', $b), ['meet_link' => 'https://zoom.us/j/1'])
            ->assertSessionHasErrorsIn('booking'.$b->id, 'meet_link');
        $tutor->post(route('tutor.bookings.accept', $b), ['meet_link' => 'https://meet.google.com/abc-defg-hij'])->assertRedirect();

        $this->assertSame(BookingStatus::Accepted, $b->fresh()->status);
        Notification::assertSentTo($this->parent, BookingAnswered::class);
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Confirmed');
        $this->actingAs($this->profile->user)->get(route('tutor.bookings'))->assertSee('Confirmed students (1)');
    }

    public function test_tutor_declines_with_a_reason(): void
    {
        $this->book();
        $b = ClassBooking::sole();

        $this->actingAs($this->profile->user)->post(route('tutor.bookings.decline', $b))->assertSessionHasErrorsIn('booking'.$b->id, 'tutor_note');
        $this->actingAs($this->profile->user)->post(route('tutor.bookings.decline', $b), ['tutor_note' => 'Tue and Thu work.']);

        $this->assertSame(BookingStatus::Declined, $b->fresh()->status);
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Declined')->assertSee('Tue and Thu work.');
    }

    public function test_other_tutors_and_late_answers_are_refused(): void
    {
        $this->book();
        $b = ClassBooking::sole();

        $other = TutorProfile::factory()->approved()->create();
        $this->actingAs($other->user)->post(route('tutor.bookings.accept', $b), ['meet_link' => 'https://meet.google.com/a-b-c'])->assertNotFound();

        $b->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->assertSame(BookingStatus::Expired, $b->currentStatus());
        $this->actingAs($this->profile->user)->post(route('tutor.bookings.accept', $b), ['meet_link' => 'https://meet.google.com/a-b-c'])->assertStatus(409);
    }
}
