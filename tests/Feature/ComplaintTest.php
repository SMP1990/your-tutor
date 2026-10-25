<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ComplaintStatus;
use App\Models\Child;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Complaint;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 6b: families and tutors report problems; admins answer; the reporter reads the reply. */
class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    private User $parent;

    private ClassBooking $booking;

    private ClassSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = TutorProfile::factory()->approved()->create();
        $this->parent = User::factory()->parent()->create(['name' => 'Sadia Malik']);
        $child = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']);
        $this->booking = tap(new ClassBooking, fn ($b) => $b->forceFill([
            'tutor_profile_id' => $this->tutor->id,
            'user_id' => $this->parent->id,
            'child_id' => $child->id,
            'student_name' => 'Ahmed',
            'grade' => 'O Level',
            'days' => ['Mon'],
            'start_time' => '17:00',
            'duration_minutes' => 60,
            'starts_on' => now()->addDay()->toDateString(),
            'weeks' => 4,
            'rate_minor' => 180000,
            'currency' => 'PKR',
            'status' => BookingStatus::Accepted,
            'meet_link' => 'https://meet.google.com/a-b-c',
            'expires_at' => now(),
        ])->save());
        $this->session = ClassSession::create(['class_booking_id' => $this->booking->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    }

    private function report(?User $as = null, array $data = [], ?string $url = null)
    {
        return $this->actingAs($as ?? $this->parent)->post($url ?? route('complaints.session.store', $this->session), $data + [
            'category' => 'late',
            'details' => 'The tutor joined twenty minutes late.',
        ]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create(['name' => 'Admin Aisha']))->withSession(['two_factor_passed' => true]);
    }

    public function test_family_reports_a_class_and_sees_it_under_my_reports(): void
    {
        $this->actingAs($this->parent)->get('/dashboard/classes')->assertSee('Report a problem');
        $this->actingAs($this->parent)->get(route('complaints.session', $this->session))->assertOk()->assertSee('Report a problem');

        $this->report()->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'user_id' => $this->parent->id, 'class_booking_id' => $this->booking->id,
            'class_session_id' => $this->session->id, 'category' => 'late', 'status' => 'new',
        ]);
        $this->actingAs($this->parent)->get('/dashboard/reports')->assertOk()
            ->assertSee('Class started late or ended early')->assertSee('The tutor joined twenty minutes late.')->assertSee('New');
    }

    public function test_family_can_report_a_whole_booking_from_the_dashboard(): void
    {
        $this->actingAs($this->parent)->get('/dashboard/parent')->assertSee(route('complaints.booking', $this->booking));

        $this->report(null, [], route('complaints.booking.store', $this->booking))->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', ['class_booking_id' => $this->booking->id, 'class_session_id' => null]);
    }

    public function test_the_tutor_can_report_too(): void
    {
        $this->actingAs($this->tutor->user)->get('/dashboard/tutor/bookings')->assertSee(route('complaints.booking', $this->booking));

        $this->report($this->tutor->user, ['category' => 'no_show'])->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', ['user_id' => $this->tutor->user_id, 'category' => 'no_show']);
    }

    public function test_strangers_and_admins_cannot_report_on_someone_elses_booking(): void
    {
        $this->post(route('complaints.session.store', $this->session))->assertRedirect('/login');

        $stranger = User::factory()->parent()->create();
        $otherTutor = TutorProfile::factory()->approved()->create();

        $this->actingAs($stranger)->get(route('complaints.session', $this->session))->assertNotFound();
        $this->report($stranger)->assertNotFound();
        $this->report($otherTutor->user)->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('complaints.index'))->assertForbidden();
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_the_form_is_validated(): void
    {
        $this->report(null, ['category' => ''])->assertSessionHasErrors('category');
        $this->report(null, ['category' => 'made-up'])->assertSessionHasErrors('category');
        $this->report(null, ['details' => 'short'])->assertSessionHasErrors('details');
        $this->report(null, ['details' => str_repeat('a', Complaint::MAX_DETAILS + 1)])->assertSessionHasErrors('details');
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_only_three_open_reports_per_booking(): void
    {
        foreach (range(1, Complaint::MAX_OPEN_PER_BOOKING) as $i) {
            $this->report()->assertRedirect();
        }
        $this->report()->assertStatus(409);
        $this->assertDatabaseCount('complaints', Complaint::MAX_OPEN_PER_BOOKING);

        Complaint::query()->first()->forceFill(['status' => ComplaintStatus::Closed])->save();
        $this->report()->assertRedirect();
    }

    public function test_admin_inbox_has_status_tabs_and_details(): void
    {
        $this->report();
        $this->report(null, ['category' => 'technical', 'details' => 'The video froze again and again.']);
        Complaint::query()->latest('id')->first()->forceFill(['status' => ComplaintStatus::Closed])->save();

        $admin = $this->asAdmin();
        $admin->get('/admin/complaints')->assertOk()->assertSee('New (1)')->assertSee('In progress (0)')->assertSee('Closed (1)')
            ->assertSee('Class started late or ended early')->assertDontSee('Video or connection problem');
        $admin->get('/admin/complaints?tab=closed')->assertSee('Video or connection problem');

        $admin->get(route('admin.complaints.show', Complaint::first()))->assertOk()
            ->assertSee('The tutor joined twenty minutes late.')->assertSee('Sadia Malik')->assertSee($this->tutor->user->name);
    }

    public function test_admin_replies_and_the_reporter_sees_the_reply_but_not_the_private_note(): void
    {
        $this->report();
        $complaint = Complaint::first();

        $this->asAdmin()->put(route('admin.complaints.update', $complaint), [
            'status' => 'in_progress',
            'admin_reply' => 'We have spoken to the tutor and will make sure this does not happen again.',
            'admin_note' => 'Tutor admitted a traffic problem.',
        ])->assertRedirect();

        $complaint->refresh();
        $this->assertSame(ComplaintStatus::InProgress, $complaint->status);
        $this->assertNotNull($complaint->replied_at);

        $this->actingAs($this->parent)->get('/dashboard/reports')
            ->assertSee('We have spoken to the tutor')->assertSee('In progress')->assertDontSee('traffic problem');
    }

    public function test_status_must_be_valid_and_only_admins_can_answer(): void
    {
        $this->report();
        $complaint = Complaint::first();

        $this->asAdmin()->put(route('admin.complaints.update', $complaint), ['status' => 'banana'])->assertSessionHasErrors('status');
        $this->actingAs($this->parent)->put(route('admin.complaints.update', $complaint), ['status' => 'closed'])->assertForbidden();
        $this->actingAs($this->parent)->get(route('admin.complaints.show', $complaint))->assertForbidden();
        $this->assertSame(ComplaintStatus::New, $complaint->fresh()->status);
    }

    public function test_one_user_cannot_read_another_users_reports(): void
    {
        $this->report();
        $other = User::factory()->parent()->create();

        $this->actingAs($other)->get('/dashboard/reports')->assertOk()->assertDontSee('The tutor joined twenty minutes late.');
    }
}
