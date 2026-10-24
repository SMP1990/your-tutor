<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
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

    public function test_classes_of_unconfirmed_bookings_are_not_shown(): void
    {
        ClassSchedule::create($this->booking); // still pending
        $this->actingAs($this->parent)->get(route('classes.index'))->assertSee('No upcoming classes');
    }
}
