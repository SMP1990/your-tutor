<?php

namespace Tests\Feature;

use App\Enums\DemoStatus;
use App\Models\DemoRequest;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 3d: admins see all free demo requests, sorted into tabs. */
class AdminDemoRequestsTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutor = TutorProfile::factory()->approved()->create();
    }

    private function demo(string $student, array $attributes = []): DemoRequest
    {
        return tap(new DemoRequest, fn ($d) => $d->forceFill($attributes + [
            'tutor_profile_id' => $this->tutor->id,
            'user_id' => User::factory()->parent()->create()->id,
            'student_name' => $student,
            'grade' => 'O Level',
            'preferred_day' => 'Sat',
            'preferred_time' => 'morning',
            'phone' => '03001234567',
            'expires_at' => now()->addHours(48),
        ])->save());
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    public function test_requests_are_sorted_into_tabs(): void
    {
        $this->demo('Waiting Wasim');
        $this->demo('Late Laila', ['expires_at' => now()->subHour()]);
        $this->demo('Future Fatima', ['status' => DemoStatus::Accepted, 'scheduled_at' => now()->addDay(), 'meet_link' => 'https://meet.google.com/a-b-c']);
        $this->demo('Past Pervaiz', ['status' => DemoStatus::Accepted, 'scheduled_at' => now()->subHour(), 'meet_link' => 'https://meet.google.com/a-b-c']);
        $this->demo('Done Danish', ['status' => DemoStatus::Completed]);
        $this->demo('Declined Dua', ['status' => DemoStatus::Declined, 'tutor_note' => 'Fully booked']);

        $admin = $this->asAdmin();
        $admin->get('/admin/demos')->assertOk()
            ->assertSee('Waiting for tutor (1)')->assertSee('Not answered in time (1)')->assertSee('Upcoming (1)')
            ->assertSee('Tutor to confirm (1)')->assertSee('Done / no-show (1)')->assertSee('Declined / cancelled (1)')
            ->assertSee('Waiting Wasim')->assertDontSee('Late Laila');

        $admin->get('/admin/demos?tab=unanswered')->assertSee('Late Laila')->assertSee('Expired')->assertDontSee('Waiting Wasim');
        $admin->get('/admin/demos?tab=upcoming')->assertSee('Future Fatima')->assertDontSee('Past Pervaiz');
        $admin->get('/admin/demos?tab=outcome')->assertSee('Past Pervaiz');
        $admin->get('/admin/demos?tab=closed')->assertSee('Declined Dua')->assertSee('Fully booked');
        $admin->get('/admin/demos?tab=nonsense')->assertOk()->assertSee('Waiting Wasim');
    }

    public function test_admin_dashboard_shows_demo_counts_and_menu_link(): void
    {
        $this->demo('Late Laila', ['expires_at' => now()->subHour()]);

        $this->asAdmin()->get('/admin')->assertSee('Free demo requests')->assertSee('1 not answered')
            ->assertSee(route('admin.demos.index'));
    }

    public function test_only_admins_with_two_step_login_get_in(): void
    {
        $this->get('/admin/demos')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->parent()->create())->get('/admin/demos')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/demos')->assertRedirect(route('two-factor.show'));
    }
}
