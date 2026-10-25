<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ClassStatus;
use App\Models\Child;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Complaint;
use App\Models\DemoRequest;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Step 7c-1: one family's data belongs to that family and its tutor only. Every route that takes an id
 * must answer 403/404 to somebody else (another parent, another tutor, a student, an admin) and change nothing.
 */
class AccessIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    private User $parent;

    private Child $child;

    private DemoRequest $demo;

    private ClassBooking $booking;

    private ClassSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tutor = TutorProfile::factory()->approved()->create();
        $this->parent = User::factory()->parent()->create();
        $this->child = Child::factory()->create(['parent_id' => $this->parent->id, 'name' => 'Ahmed']);

        $this->demo = tap(new DemoRequest, fn ($d) => $d->forceFill([
            'tutor_profile_id' => $this->tutor->id, 'user_id' => $this->parent->id, 'child_id' => $this->child->id,
            'subject_id' => Subject::factory()->create()->id, 'student_name' => 'Ahmed', 'grade' => 'O Level',
            'preferred_day' => 'Sat', 'preferred_time' => 'evening', 'phone' => '03001234567',
            'expires_at' => now()->addHours(48),
        ])->save());

        $this->booking = tap(new ClassBooking, fn ($b) => $b->forceFill([
            'tutor_profile_id' => $this->tutor->id, 'user_id' => $this->parent->id, 'child_id' => $this->child->id,
            'student_name' => 'Ahmed', 'grade' => 'O Level', 'days' => ['Mon'], 'start_time' => '17:00',
            'duration_minutes' => 60, 'starts_on' => now()->addDay()->toDateString(), 'weeks' => 4,
            'rate_minor' => 180000, 'currency' => 'PKR', 'status' => BookingStatus::Accepted,
            'meet_link' => 'https://meet.google.com/a-b-c', 'expires_at' => now(),
        ])->save());

        $this->session = ClassSession::create(['class_booking_id' => $this->booking->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    }

    /** @return array<string, User> people who must NOT reach this family's data */
    private function outsiders(): array
    {
        return [
            'other parent' => User::factory()->parent()->create(),
            'other student' => User::factory()->create(),
            'other tutor' => TutorProfile::factory()->approved()->create()->user,
            'admin' => User::factory()->admin()->create(),
        ];
    }

    /** @return list<array{0: string, 1: string, 2: array}> method, url, payload for routes with an id in them */
    private function ownedRoutes(): array
    {
        $report = ['category' => 'late', 'details' => 'The tutor joined twenty minutes late.'];

        return [
            ['POST', route('demos.cancel', $this->demo), []],
            ['GET', route('demos.join', $this->demo), []],
            ['GET', route('bookings.create', $this->demo), []],
            ['POST', route('bookings.cancel', $this->booking), []],
            ['GET', route('reviews.edit', $this->booking), []],
            ['PUT', route('reviews.update', $this->booking), ['rating' => 1, 'comment' => 'Vandalism by an outsider.']],
            ['GET', route('complaints.booking', $this->booking), []],
            ['POST', route('complaints.booking.store', $this->booking), $report],
            ['GET', route('complaints.session', $this->session), []],
            ['POST', route('complaints.session.store', $this->session), $report],
            ['GET', route('classes.join', $this->session), []],
            ['POST', route('classes.cancel', $this->session), []],
            ['POST', route('classes.mark', $this->session), ['status' => 'held']],
            ['GET', route('children.edit', $this->child), []],
            ['PUT', route('children.update', $this->child), ['name' => 'Hacked', 'grade' => 'Grade 5']],
            ['DELETE', route('children.destroy', $this->child), []],
        ];
    }

    public function test_outsiders_get_403_or_404_on_every_owned_route_and_nothing_changes(): void
    {
        foreach ($this->outsiders() as $who => $outsider) {
            foreach ($this->ownedRoutes() as [$method, $url, $payload]) {
                $status = $this->actingAs($outsider)->withSession(['two_factor_passed' => true])->call($method, $url, $payload)->getStatusCode();

                $this->assertContains($status, [403, 404], "{$who} got {$status} on {$method} {$url}");
            }
        }

        $this->assertSame('pending', $this->demo->fresh()->status->value);
        $this->assertSame(BookingStatus::Accepted, $this->booking->fresh()->status);
        $this->assertSame(ClassStatus::Scheduled, $this->session->fresh()->status);
        $this->assertSame('Ahmed', $this->child->fresh()->name);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_guests_are_sent_to_login_on_every_owned_route(): void
    {
        foreach ($this->ownedRoutes() as [$method, $url, $payload]) {
            $this->call($method, $url, $payload)->assertRedirect('/login');
        }
    }

    public function test_the_tutor_side_routes_refuse_other_tutors_families_students_and_guests(): void
    {
        $routes = [
            ['POST', route('tutor.demos.accept', $this->demo), ['scheduled_at' => '2026-12-26T17:00', 'meet_link' => 'https://meet.google.com/abc-defg-hij']],
            ['POST', route('tutor.demos.decline', $this->demo), ['tutor_note' => 'No']],
            ['POST', route('tutor.demos.finish', $this->demo), ['outcome' => 'completed']],
            ['POST', route('tutor.bookings.accept', $this->booking), ['meet_link' => 'https://meet.google.com/abc-defg-hij']],
            ['POST', route('tutor.bookings.decline', $this->booking), ['tutor_note' => 'No']],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->call($method, $url, $payload)->assertRedirect('/login'); // guests first: nobody is logged in yet
        }
        foreach ($this->outsiders() + ['the family itself' => $this->parent] as $who => $outsider) {
            foreach ($routes as [$method, $url, $payload]) {
                $status = $this->actingAs($outsider)->withSession(['two_factor_passed' => true])->call($method, $url, $payload)->getStatusCode();

                // Another tutor must be told "not yours" (404); everyone else is stopped by the role (403).
                $this->assertContains($status, [403, 404], "{$who} got {$status} on {$method} {$url}");
            }
        }

        $this->assertSame('pending', $this->demo->fresh()->status->value);
    }

    public function test_only_the_family_and_its_tutor_can_report_and_nobody_reads_other_peoples_reports(): void
    {
        $this->actingAs($this->parent)->post(route('complaints.booking.store', $this->booking), ['category' => 'late', 'details' => 'Private words of the family.'])->assertRedirect();
        $this->actingAs($this->tutor->user)->post(route('complaints.booking.store', $this->booking), ['category' => 'no_show', 'details' => 'Private words of the tutor.'])->assertRedirect();
        $this->assertDatabaseCount('complaints', 2);

        $this->actingAs($this->parent)->get('/dashboard/reports')->assertSee('Private words of the family.')->assertDontSee('Private words of the tutor.');
        $this->actingAs($this->tutor->user)->get('/dashboard/reports')->assertSee('Private words of the tutor.')->assertDontSee('Private words of the family.');
        $this->actingAs(User::factory()->parent()->create())->get('/dashboard/reports')->assertDontSee('Private words');

        $complaint = Complaint::first();
        foreach ($this->outsiders() as $who => $outsider) {
            $status = $this->actingAs($outsider)->get(route('admin.complaints.show', $complaint))->getStatusCode();

            // Non-admins: 403. An admin who has not typed the emailed code yet: sent to the code page (302).
            $this->assertContains($status, [302, 403], "{$who} got {$status}");
        }
    }

    public function test_every_admin_route_is_closed_to_non_admins_and_to_admins_without_the_code(): void
    {
        $admin = User::factory()->admin()->create();
        $urls = ['/admin', '/admin/users', '/admin/tutors', '/admin/demos', '/admin/bookings', '/admin/classes',
            '/admin/reviews', '/admin/complaints', '/admin/messages', '/admin/settings'];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login'); // guest
        }
        foreach ($urls as $url) {
            foreach ([$this->parent, $this->tutor->user, User::factory()->create()] as $user) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
            $this->actingAs($admin)->get($url)->assertRedirect(route('two-factor.show')); // logged in, but no code yet
        }
        foreach ($urls as $url) { // only now give the admin the code (the session would otherwise remember it)
            $this->actingAs($admin)->withSession(['two_factor_passed' => true])->get($url)->assertOk();
        }
    }

    public function test_private_documents_are_only_for_admins_with_the_code(): void
    {
        $profile = $this->tutor;
        $url = route('admin.tutors.document', [$profile->id, 'cnic-front']);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($profile->user)->get($url)->assertForbidden();
        $this->actingAs($this->parent)->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertRedirect(route('two-factor.show'));
        $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true])
            ->get(route('admin.tutors.document', [$profile->id, '../../../.env']))->assertNotFound();
    }

    public function test_a_review_can_only_be_edited_by_its_own_family(): void
    {
        $this->session->forceFill(['status' => ClassStatus::Held])->save();
        $this->actingAs($this->parent)->put(route('reviews.update', $this->booking), ['rating' => 5, 'comment' => 'Excellent teacher, very clear.'])->assertRedirect();
        $review = Review::first();

        $other = User::factory()->parent()->create();
        $this->actingAs($other)->put(route('reviews.update', $this->booking), ['rating' => 1, 'comment' => 'Vandalism by an outsider.'])->assertNotFound();
        $this->assertSame(5, $review->fresh()->rating);
    }
}
