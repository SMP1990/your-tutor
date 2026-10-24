<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'student' => '/dashboard/student',
        'parent' => '/dashboard/parent',
        'tutor' => '/dashboard/tutor',
        'admin' => '/admin',
    ];

    /** Admin pages need the emailed code; mark it as done for access tests. */
    private function as(Role $role): User
    {
        $user = User::factory()->role($role)->create();
        $this->actingAs($user);
        if ($role === Role::Admin) {
            $this->withSession(['two_factor_passed' => true]);
        }

        return $user;
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        foreach (array_merge(['/dashboard'], self::PAGES) as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_unverified_users_must_verify_first(): void
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_each_role_lands_on_its_own_dashboard_and_nobody_elses(): void
    {
        foreach (Role::cases() as $role) {
            $this->as($role);
            $this->get('/dashboard')->assertRedirect(self::PAGES[$role->value]);

            foreach (self::PAGES as $pageRole => $url) {
                $pageRole === $role->value
                    ? $this->get($url)->assertOk()
                    : $this->get($url)->assertForbidden();
            }
        }
    }

    public function test_login_goes_to_the_dashboard(): void
    {
        User::factory()->parent()->create(['email' => 'p@example.test', 'password' => 'secret123']);

        $this->post('/login', ['login' => 'p@example.test', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
    }

    public function test_parent_sees_only_their_own_children(): void
    {
        $other = User::factory()->parent()->create();
        $other->children()->create(['name' => 'Zara', 'grade' => 'Grade 4']);

        $parent = $this->as(Role::Parent);
        $parent->children()->create(['name' => 'Ahmed', 'grade' => 'FSc']);

        $this->get('/dashboard/parent')->assertSee('Ahmed')->assertDontSee('Zara');
    }

    public function test_tutor_sees_their_application_status(): void
    {
        $tutor = $this->as(Role::Tutor);
        $this->get('/dashboard/tutor')->assertSee('Not started');

        $profile = TutorProfile::factory()->create(['user_id' => $tutor->id]);
        $this->actingAs($tutor->fresh())->get('/dashboard/tutor')->assertDontSee('View my public profile');

        $profile->forceFill(['status' => \App\Enums\TutorStatus::Approved])->save();
        $this->actingAs($tutor->fresh())->get('/dashboard/tutor')
            ->assertSee('View my public profile')->assertSee(route('tutors.show', $profile->slug), false);
        $this->get(route('tutors.show', $profile->slug))->assertOk();
    }

    public function test_admin_dashboard_shows_live_numbers(): void
    {
        User::factory()->count(3)->parent()->create();
        $this->as(Role::Admin);

        $this->get('/admin')->assertOk()->assertSee('Parents')->assertSee('Newest accounts');
    }

    public function test_admin_must_enter_the_emailed_code(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/admin')->assertRedirect(route('two-factor.show'));
        $this->get('/two-factor')->assertOk();

        $code = null;
        Notification::assertSentTo($admin, TwoFactorCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $wrong = $code === '111111' ? '222222' : '111111';
        $this->post('/two-factor', ['code' => $wrong])->assertSessionHasErrors('code');
        $this->get('/admin')->assertRedirect(route('two-factor.show'));

        $this->post('/two-factor', ['code' => $code])->assertRedirect(route('dashboard.admin'));
        $this->get('/admin')->assertOk();
    }

    public function test_code_stops_working_after_five_wrong_tries(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/two-factor');

        $code = null;
        Notification::assertSentTo($admin, TwoFactorCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $wrong = $code === '111111' ? '222222' : '111111';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/two-factor', ['code' => $wrong]);
        }

        $this->post('/two-factor', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_other_roles_never_get_the_code_page(): void
    {
        $this->as(Role::Tutor);

        $this->get('/two-factor')->assertForbidden()->assertSee('This page is not for your account');
    }

    public function test_dashboard_has_a_real_log_out_button(): void
    {
        $this->as(Role::Student);

        $this->get('/dashboard/student')->assertSee(route('logout'))->assertDontSee('Preview with sample data');
    }
}
