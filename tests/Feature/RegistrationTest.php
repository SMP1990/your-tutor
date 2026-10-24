<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'role' => 'parent',
            'name' => 'Sadia Malik',
            'phone' => '0300 123-4567',
            'email' => ' Sadia@Example.TEST ',
            'password' => 'secret123',
            'whatsapp_ok' => '1',
            'terms' => '1',
        ];
    }

    public function test_a_parent_can_register_and_gets_a_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', $this->form())->assertRedirect(route('verification.notice'));

        $user = User::firstWhere('email', 'sadia@example.test');
        $this->assertSame(Role::Parent, $user->role);
        $this->assertSame('03001234567', $user->phone);
        $this->assertTrue($user->whatsapp_opt_in);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->post('/register', $this->form(['role' => 'admin']))->assertSessionHasErrors('role');
        $this->assertSame(0, User::count());
    }

    public function test_tutor_role_is_saved(): void
    {
        $this->post('/register', $this->form(['role' => 'tutor']));
        $this->assertSame(Role::Tutor, User::first()->role);
    }

    public function test_validation_errors(): void
    {
        $this->post('/register', $this->form([
            'phone' => '12345', 'password' => 'short', 'terms' => '', 'name' => '',
        ]))->assertSessionHasErrors(['phone', 'password', 'terms', 'name']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'sadia@example.test']);

        $this->post('/register', $this->form())->assertSessionHasErrors('email');
    }

    public function test_password_is_stored_hashed(): void
    {
        $this->post('/register', $this->form());
        $this->assertNotSame('secret123', User::first()->getRawOriginal('password'));
    }

    public function test_verification_link_verifies_the_email_even_when_logged_out(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_or_expired_links_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $wrongHash = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.test')]);
        $this->get($wrongHash)->assertForbidden();

        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expired)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_notice_page_needs_login_and_shows_the_email(): void
    {
        $this->get('/email/verify')->assertRedirect(route('login'));

        $user = User::factory()->unverified()->create(['email' => 'me@example.test']);
        $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('me@example.test');
    }
}
