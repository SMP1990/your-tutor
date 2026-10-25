<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Step 7a: the first admin is made from the server's command line. */
class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-2026';

    public function test_creates_a_verified_admin_with_a_hashed_password(): void
    {
        $this->artisan('admin:create', ['email' => 'Boss@Example.test', '--name' => 'Adnan Rashid', '--phone' => '0300 1234567'])
            ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->expectsOutputToContain('Admin created')
            ->assertSuccessful();

        $admin = User::where('email', 'boss@example.test')->firstOrFail();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertSame('03001234567', $admin->phone);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
        $this->assertNotSame(self::PASSWORD, $admin->password);
    }

    public function test_asks_for_the_name_when_it_is_not_given(): void
    {
        $this->artisan('admin:create', ['email' => 'boss@example.test'])
            ->expectsQuestion('Full name', 'Adnan Rashid')
            ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'boss@example.test', 'name' => 'Adnan Rashid', 'role' => 'admin']);
    }

    public function test_refuses_weak_or_mismatched_passwords_and_bad_emails(): void
    {
        foreach ([['short1', 'short1'], ['onlyletterslongenough', 'onlyletterslongenough'], [self::PASSWORD, 'Something-Else-2026']] as [$one, $two]) {
            $this->artisan('admin:create', ['email' => 'boss@example.test', '--name' => 'Adnan'])
                ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', $one)
                ->expectsQuestion('Password again', $two)
                ->assertFailed();
        }

        $this->artisan('admin:create', ['email' => 'not-an-email', '--name' => 'Adnan'])
            ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_refuses_an_email_that_is_already_taken(): void
    {
        User::factory()->parent()->create(['email' => 'boss@example.test']);

        $this->artisan('admin:create', ['email' => 'boss@example.test', '--name' => 'Adnan'])
            ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(Role::Parent, User::first()->role);
    }

    public function test_the_new_admin_can_log_in_and_is_asked_for_the_code(): void
    {
        $this->artisan('admin:create', ['email' => 'boss@example.test', '--name' => 'Adnan'])
            ->expectsQuestion('Password (at least 12 characters, with letters and numbers)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->assertSuccessful();

        $this->post('/login', ['login' => 'boss@example.test', 'password' => self::PASSWORD])->assertRedirect();
        $this->assertAuthenticated();
        $this->get('/admin')->assertRedirect(route('two-factor.show'));
    }
}
