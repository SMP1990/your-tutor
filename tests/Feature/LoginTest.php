<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'email' => 'sadia@example.test',
            'phone' => '03001234567',
            'password' => 'secret123',
        ]);
    }

    public function test_log_in_with_email(): void
    {
        $user = $this->user();

        $this->post('/login', ['login' => 'Sadia@Example.test', 'password' => 'secret123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_log_in_with_phone_number(): void
    {
        $user = $this->user();

        $this->post('/login', ['login' => '0300 123-4567', 'password' => 'secret123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_shared_phone_number_cannot_be_used_to_log_in(): void
    {
        $this->user();
        $this->user(['email' => 'other@example.test']);

        $this->post('/login', ['login' => '03001234567', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_with_a_general_message(): void
    {
        $this->user();

        $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'wrong-123'])
            ->assertSessionHasErrors(['login' => 'These details do not match an account.']);
        $this->assertGuest();
    }

    public function test_too_many_wrong_attempts_lock_the_login(): void
    {
        $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'wrong-123']);
        }

        $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_unverified_users_are_sent_to_verify_their_email(): void
    {
        $this->user(['email_verified_at' => null]);

        $this->post('/login', ['login' => 'sadia@example.test', 'password' => 'secret123'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_log_out(): void
    {
        $this->actingAs($this->user())->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_header_shows_name_and_log_out_when_logged_in(): void
    {
        $this->actingAs($this->user(['name' => 'Sadia Malik']))->get('/')
            ->assertSee('Hi, Sadia')
            ->assertSee(route('logout'))
            ->assertDontSee('>Register</a>', false);
    }

    public function test_logged_in_users_are_sent_away_from_login_and_register(): void
    {
        $this->actingAs($this->user());

        $this->get('/login')->assertRedirect();
        $this->get('/register')->assertRedirect();
    }

    public function test_forgot_password_sends_a_link_but_never_reveals_if_an_email_exists(): void
    {
        Notification::fake();
        $user = $this->user();

        $known = $this->post('/forgot-password', ['email' => 'sadia@example.test']);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.test']);

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertSame($known->getSession()->get('notice'), $unknown->getSession()->get('notice'));
    }

    public function test_reset_password_with_a_valid_link(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email=sadia@example.test")->assertOk()->assertSee('Set a new password');
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'sadia@example.test',
            'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_reset_with_a_wrong_token_fails(): void
    {
        $user = $this->user();

        $this->post('/reset-password', [
            'token' => 'not-a-real-token', 'email' => 'sadia@example.test',
            'password' => 'newpass123', 'password_confirmation' => 'newpass123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }
}
