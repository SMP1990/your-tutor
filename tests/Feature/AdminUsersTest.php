<?php

namespace Tests\Feature;

use App\Models\AdminAction;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 6a: admins list and search users, suspend / re-open accounts and verify an email by hand. */
class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Aisha']);
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['two_factor_passed' => true]);
    }

    public function test_only_admins_with_the_code_can_open_the_users_pages(): void
    {
        $user = User::factory()->parent()->create();

        $this->get('/admin/users')->assertRedirect('/login');
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.suspend', $user), ['reason' => 'Trying myself'])->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/users')->assertRedirect(route('two-factor.show'));
        $this->asAdmin()->get('/admin/users')->assertOk()->assertSee('Admin Aisha');
    }

    public function test_search_and_filters(): void
    {
        User::factory()->parent()->create(['name' => 'Sadia Malik', 'email' => 'sadia@example.test', 'phone' => '03001112222']);
        User::factory()->tutor()->create(['name' => 'Bilal Tutor']);
        User::factory()->parent()->unverified()->create(['name' => 'Zara Unverified']);
        $gone = User::factory()->parent()->create(['name' => 'Hina Suspended']);
        $gone->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Spam'])->save();

        $admin = $this->asAdmin();
        $admin->get('/admin/users?q=sadia')->assertSee('Sadia Malik')->assertDontSee('Bilal Tutor');
        $admin->get('/admin/users?q=03001112222')->assertSee('Sadia Malik')->assertDontSee('Bilal Tutor');
        $admin->get('/admin/users?role=tutor')->assertSee('Bilal Tutor')->assertDontSee('Sadia Malik');
        $admin->get('/admin/users?status=suspended')->assertSee('Hina Suspended')->assertDontSee('Sadia Malik');
        $admin->get('/admin/users?status=unverified')->assertSee('Zara Unverified')->assertDontSee('Sadia Malik');
        $admin->get('/admin/users?q=%25')->assertOk()->assertSee('No users match');
    }

    public function test_user_detail_shows_the_facts(): void
    {
        $parent = User::factory()->parent()->create(['name' => 'Sadia Malik']);
        $parent->children()->create(['name' => 'Ali', 'grade' => 'Grade 5']);

        $this->asAdmin()->get(route('admin.users.show', $parent))->assertOk()
            ->assertSee('Sadia Malik')->assertSee('Ali (Grade 5)')->assertSee('Free demo requests')
            ->assertSee('Suspend account')->assertSee('No admin has done anything');
    }

    public function test_suspending_needs_a_reason_and_is_recorded(): void
    {
        $parent = User::factory()->parent()->create();
        $admin = $this->asAdmin();

        $admin->post(route('admin.users.suspend', $parent), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertFalse($parent->fresh()->isSuspended());

        $admin->post(route('admin.users.suspend', $parent), ['reason' => 'Abusive messages to a tutor'])->assertRedirect();
        $this->assertTrue($parent->fresh()->isSuspended());
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id, 'user_id' => $parent->id,
            'action' => AdminAction::SUSPENDED, 'reason' => 'Abusive messages to a tutor',
        ]);
        $admin->get(route('admin.users.show', $parent))->assertSee('Abusive messages to a tutor')->assertSee('Admin Aisha')
            ->assertSee('Make account active again');

        $admin->post(route('admin.users.suspend', $parent), ['reason' => 'Again please'])->assertStatus(409);
    }

    public function test_unsuspending_is_recorded_too(): void
    {
        $parent = User::factory()->parent()->create();
        $parent->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Spam'])->save();

        $this->asAdmin()->post(route('admin.users.unsuspend', $parent), ['reason' => 'It was a mistake'])->assertRedirect();

        $this->assertFalse($parent->fresh()->isSuspended());
        $this->assertNull($parent->fresh()->suspended_reason);
        $this->assertDatabaseHas('admin_actions', ['user_id' => $parent->id, 'action' => AdminAction::UNSUSPENDED, 'reason' => 'It was a mistake']);
    }

    public function test_an_admin_cannot_suspend_themselves_or_another_admin(): void
    {
        $other = User::factory()->admin()->create();
        $admin = $this->asAdmin();

        $admin->post(route('admin.users.suspend', $this->admin), ['reason' => 'Locking myself out'])->assertForbidden();
        $admin->post(route('admin.users.suspend', $other), ['reason' => 'Locking the other admin'])->assertForbidden();
        $admin->get(route('admin.users.show', $other))->assertOk()->assertSee('cannot suspend')->assertDontSee('Suspend account');

        $this->assertFalse($this->admin->fresh()->isSuspended());
        $this->assertFalse($other->fresh()->isSuspended());
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_a_suspended_user_cannot_log_in_and_a_logged_in_one_is_thrown_out(): void
    {
        $parent = User::factory()->parent()->create(['email' => 'sadia@example.test']);
        $parent->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Spam'])->save();

        $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'This account has been suspended. Please contact support.']);
        $this->assertGuest();
        $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['login' => 'These details do not match an account.']);

        $active = User::factory()->parent()->create();
        $this->actingAs($active)->get('/dashboard/parent')->assertOk();
        $active->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Spam'])->save();
        $this->actingAs($active->fresh())->get('/dashboard/parent')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_a_suspended_tutor_disappears_from_public_pages(): void
    {
        $profile = TutorProfile::factory()->approved()->create();
        $slug = $profile->slug;

        $this->get("/tutors/{$slug}")->assertOk();

        $profile->user->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Complaint'])->save();

        $this->get("/tutors/{$slug}")->assertNotFound();
        $this->get('/tutors')->assertDontSee($profile->user->name);
    }

    public function test_admin_can_verify_an_email_with_a_record(): void
    {
        $parent = User::factory()->parent()->unverified()->create();
        $admin = $this->asAdmin();

        $admin->post(route('admin.users.verify-email', $parent), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertFalse($parent->fresh()->hasVerifiedEmail());

        $admin->post(route('admin.users.verify-email', $parent), ['reason' => 'Confirmed by phone call'])->assertRedirect();

        $this->assertTrue($parent->fresh()->hasVerifiedEmail());
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $this->admin->id, 'user_id' => $parent->id,
            'action' => AdminAction::EMAIL_VERIFIED, 'reason' => 'Confirmed by phone call',
        ]);
        $admin->post(route('admin.users.verify-email', $parent), ['reason' => 'Second time'])->assertStatus(409);
    }
}
