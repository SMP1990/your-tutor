<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Step 4b: a confirmed booking becomes dated classes; family and tutor see them and join on time. */
class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $profile;

    private User $parent;

    private ClassBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setTimezone('UTC')->setDateTime(2026, 10, 24, 6, 0)); // Sat 24 Oct, 11:00 PKT

        $this->profile = TutorProfile::factory()->approved()->create();
        $this->parent = User::factory()->parent()->create();
        $this->booking = tap(new ClassBooking, fn ($b) => $b->forceFill([
            'tutor_profile_id' => $this->profile->id,
            'user_id' => $this->parent->id,
            'subject_id' => Subject::factory()->create(['name' => 'Physics'])->id,
            'student_name' => 'Ahmed',
            'grade' => 'O Level',
            'days' => ['Mon', 'Wed'],
            'start_time' => '17:00',
            'duration_minutes' => 60,
            'starts_on' => '2026-10-28', // a Wednesday
            'weeks' => 2,
            'rate_minor' => 180000,
            'currency' => 'PKR',
            'expires_at' => now()->addHours(48),
        ])->save());
    }

    private function confirm(): void
    {
        $this->actingAs($this->profile->user)->post(route('tutor.bookings.accept', $this->booking), [
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertRedirect();
    }

    public function test_schedule_has_days_times_weeks_classes_in_pakistan_time(): void
    {
        $times = ClassSchedule::times($this->booking);
        $pkt = array_map(fn ($t) => $t['starts_at']->copy()->timezone('Asia/Karachi')->format('D j M H:i'), $times);

        // Week 1 = Wed 28 Oct .. Tue 3 Nov; week 2 = Wed 4 Nov .. Tue 10 Nov.
        $this->assertSame(['Wed 28 Oct 17:00', 'Mon 2 Nov 17:00', 'Wed 4 Nov 17:00', 'Mon 9 Nov 17:00'], $pkt);
        $this->assertSame('12:00', $times[0]['starts_at']->format('H:i')); // stored in UTC
        $this->assertSame('13:00', $times[0]['ends_at']->format('H:i'));
    }

    public function test_confirming_creates_every_class_once(): void
    {
        $this->confirm();
        $this->assertSame(4, $this->booking->sessions()->count());
        $this->assertSame(BookingStatus::Accepted, $this->booking->fresh()->status);

        ClassSchedule::create($this->booking->fresh()); // running again adds nothing
        $this->assertSame(4, ClassSession::count());
    }

    public function test_family_and_tutor_see_the_timetable(): void
    {
        $this->confirm();

        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('Next classes')->assertSee('Wed 28 Oct, 5:00 PM');
        $this->actingAs($this->parent)->get(route('classes.index'))->assertOk()->assertSee('Mon 9 Nov, 5:00 PM')->assertSee('Physics');
        $this->actingAs($this->profile->user)->get(route('classes.index'))->assertOk()->assertSee('My timetable')->assertSee('Ahmed');
        $this->actingAs($this->profile->user)->get('/dashboard/tutor')->assertSee('Next classes');

        // Someone else's classes stay hidden.
        $this->actingAs(User::factory()->parent()->create())->get(route('classes.index'))->assertDontSee('Ahmed');
    }

    public function test_join_only_for_the_class_people_and_only_around_class_time(): void
    {
        $this->confirm();
        $first = $this->booking->sessions()->orderBy('starts_at')->first();
        $join = route('classes.join', $first);

        $this->actingAs($this->parent)->get($join)->assertRedirect(route('classes.index'));

        $this->travelTo(now()->setDateTime(2026, 10, 28, 11, 50)); // 16:50 PKT
        $this->actingAs($this->parent)->get($join)->assertRedirect('https://meet.google.com/abc-defg-hij');
        $this->actingAs($this->profile->user)->get($join)->assertRedirect('https://meet.google.com/abc-defg-hij');
        $this->actingAs(User::factory()->parent()->create())->get($join)->assertNotFound();
        $this->actingAs(TutorProfile::factory()->approved()->create()->user)->get($join)->assertNotFound();

        $this->travelTo(now()->setDateTime(2026, 10, 28, 13, 1)); // class ended
        $this->actingAs($this->parent)->get($join)->assertRedirect(route('classes.index'));
        $this->actingAs($this->parent)->get(route('classes.index'))->assertDontSee('Wed 28 Oct, 5:00 PM');
    }

    // ---- Step 4c: attendance ----

    private function first(): ClassSession
    {
        return $this->booking->sessions()->orderBy('starts_at')->first();
    }

    public function test_tutor_marks_attendance_only_after_the_class_started(): void
    {
        $this->confirm();
        $class = $this->first();
        $tutor = $this->actingAs($this->profile->user);

        $tutor->post(route('classes.mark', $class), ['status' => 'held'])->assertStatus(409);

        $this->travelTo(now()->setDateTime(2026, 10, 28, 12, 30)); // during the class
        $tutor->get(route('classes.index'))->assertSee('Mark attendance (1)');
        $tutor->post(route('classes.mark', $class), ['status' => 'held'])->assertRedirect();

        $this->assertSame(ClassStatus::Held, $class->fresh()->status);
        $this->assertSame($this->profile->user_id, $class->fresh()->marked_by);
        $tutor->post(route('classes.mark', $class), ['status' => 'student_absent'])->assertStatus(409); // already marked

        $this->actingAs($this->parent)->get(route('classes.index'))->assertSee('Past and cancelled classes')->assertSee('Class held');
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee('1 of 4 classes held');
    }

    public function test_tutor_cancels_with_a_reason_and_others_cannot_mark(): void
    {
        $this->confirm();
        $class = $this->first();

        $this->actingAs($this->profile->user)->post(route('classes.mark', $class), ['status' => 'tutor_cancelled'])
            ->assertSessionHasErrorsIn('class'.$class->id, 'note');
        $this->actingAs($this->profile->user)->post(route('classes.mark', $class), ['status' => 'tutor_cancelled', 'note' => 'Unwell']);
        $this->assertSame(ClassStatus::TutorCancelled, $class->fresh()->status);
        $this->actingAs($this->parent)->get(route('classes.index'))->assertSee('Cancelled by tutor')->assertSee('Unwell');

        $other = TutorProfile::factory()->approved()->create();
        $this->actingAs($other->user)->post(route('classes.mark', $this->booking->sessions()->latest('starts_at')->first()), ['status' => 'tutor_cancelled', 'note' => 'x'])->assertNotFound();
        $this->actingAs($this->parent)->post(route('classes.mark', $class), ['status' => 'held'])->assertForbidden();
        $this->actingAs($this->profile->user)->post(route('classes.mark', $class), ['status' => 'family_cancelled'])->assertSessionHasErrorsIn('class'.$class->id, 'status');
    }

    public function test_family_cancels_only_twelve_hours_ahead(): void
    {
        $this->confirm();
        $class = $this->first(); // Wed 28 Oct 12:00 UTC

        $this->travelTo(now()->setDateTime(2026, 10, 28, 1, 0)); // 11 hours before
        $this->actingAs($this->parent)->post(route('classes.cancel', $class))->assertStatus(409);

        $this->travelTo(now()->setDateTime(2026, 10, 27, 23, 0)); // 13 hours before
        $this->actingAs($this->parent)->get(route('classes.index'))->assertSee(route('classes.cancel', $class));
        $this->actingAs($this->parent)->post(route('classes.cancel', $class), ['note' => 'School trip'])->assertRedirect();

        $this->assertSame(ClassStatus::FamilyCancelled, $class->fresh()->status);
        $this->actingAs($this->profile->user)->get(route('classes.index'))->assertSee('Cancelled by family')->assertSee('School trip');
        $this->actingAs(User::factory()->parent()->create())->post(route('classes.cancel', $this->booking->sessions()->latest('starts_at')->first()))->assertNotFound();
    }

    public function test_classes_of_unconfirmed_bookings_are_not_shown(): void
    {
        ClassSchedule::create($this->booking); // still pending
        $this->actingAs($this->parent)->get(route('classes.index'))->assertSee('No upcoming classes');
    }
}
