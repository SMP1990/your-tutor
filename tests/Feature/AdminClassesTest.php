<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 4d: admins see every regular-class booking and every class, sorted into tabs. */
class AdminClassesTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = TutorProfile::factory()->approved()->create();
    }

    private function booking(string $student, array $attributes = []): ClassBooking
    {
        return tap(new ClassBooking, fn ($b) => $b->forceFill($attributes + [
            'tutor_profile_id' => $this->tutor->id,
            'user_id' => User::factory()->parent()->create()->id,
            'student_name' => $student,
            'grade' => 'O Level',
            'days' => ['Mon'],
            'start_time' => '17:00',
            'duration_minutes' => 60,
            'starts_on' => now()->addDays(2)->toDateString(),
            'weeks' => 4,
            'rate_minor' => 180000,
            'currency' => 'PKR',
            'expires_at' => now()->addHours(48),
        ])->save());
    }

    private function classAt(ClassBooking $booking, string $startsAt, ClassStatus $status = ClassStatus::Scheduled, ?string $note = null): ClassSession
    {
        $s = ClassSession::create(['class_booking_id' => $booking->id, 'starts_at' => $startsAt, 'ends_at' => now()->parse($startsAt)->addHour()]);
        $s->forceFill(['status' => $status, 'note' => $note])->save();

        return $s;
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    public function test_bookings_are_sorted_into_tabs(): void
    {
        $this->booking('Waiting Wasim');
        $this->booking('Late Laila', ['expires_at' => now()->subHour()]);
        $this->booking('Confirmed Fatima', ['status' => BookingStatus::Accepted, 'meet_link' => 'https://meet.google.com/a-b-c']);
        $this->booking('Declined Dua', ['status' => BookingStatus::Declined, 'tutor_note' => 'No time']);

        $admin = $this->asAdmin();
        $admin->get('/admin/bookings')->assertOk()
            ->assertSee('Waiting for tutor (1)')->assertSee('Not answered in time (1)')->assertSee('Confirmed (1)')
            ->assertSee('Declined / cancelled (1)')->assertSee('Waiting Wasim')->assertDontSee('Late Laila');
        $admin->get('/admin/bookings?tab=confirmed')->assertSee('Confirmed Fatima')->assertSee('PKR 1,800 / hour');
        $admin->get('/admin/bookings?tab=closed')->assertSee('Declined Dua')->assertSee('No time');
    }

    public function test_classes_are_sorted_into_tabs(): void
    {
        $b = $this->booking('Ahmed', ['status' => BookingStatus::Accepted, 'meet_link' => 'https://meet.google.com/a-b-c']);
        $this->classAt($b, now()->addDay()->toDateTimeString());
        $this->classAt($b, now()->subDays(2)->toDateTimeString());
        $this->classAt($b, now()->subDays(3)->toDateTimeString(), ClassStatus::Held);
        $this->classAt($b, now()->subDays(4)->toDateTimeString(), ClassStatus::StudentAbsent);
        $this->classAt($b, now()->subDays(5)->toDateTimeString(), ClassStatus::TutorCancelled, 'Unwell');

        $admin = $this->asAdmin();
        $admin->get('/admin/classes')->assertOk()
            ->assertSee('Upcoming (1)')->assertSee('Attendance not marked (1)')->assertSee('Held (1)')
            ->assertSee('Student absent (1)')->assertSee('Cancelled (1)');
        $admin->get('/admin/classes?tab=unmarked')->assertSee('Not marked');
        $admin->get('/admin/classes?tab=cancelled')->assertSee('Cancelled by tutor')->assertSee('Unwell');

        $admin->get('/admin')->assertSee('Regular classes')->assertSee('1 not marked')->assertSee(route('admin.classes.index'));
    }

    public function test_only_admins_with_two_step_login_get_in(): void
    {
        foreach (['/admin/bookings', '/admin/classes'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['/admin/bookings', '/admin/classes'] as $url) {
            $this->actingAs($this->tutor->user)->get($url)->assertForbidden();
            $this->actingAs(User::factory()->admin()->create())->get($url)->assertRedirect(route('two-factor.show'));
        }
    }
}
