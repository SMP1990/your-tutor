<?php

namespace Tests\Feature;

use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\ConfirmNewEmail;
use App\Notifications\EmailChangeRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/** Profile page for every role: name + phone, email (confirmed through the new inbox), password. */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password'; // UserFactory default

    private function user(array $attributes = []): User
    {
        return User::factory()->parent()->create($attributes + ['name' => 'Sadia Malik', 'email' => 'sadia@example.test', 'phone' => '03001112222']);
    }

    public function test_every_role_can_open_it_and_guests_cannot(): void
    {
        $this->get('/dashboard/profile')->assertRedirect('/login');

        foreach ([User::factory()->create(), $this->user(), TutorProfile::factory()->approved()->create()->user] as $user) {
            $this->actingAs($user)->get('/dashboard/profile')->assertOk()->assertSee('Your profile')->assertSee($user->email);
        }

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/dashboard/profile')->assertRedirect(route('two-factor.show'));
        $this->actingAs($admin)->withSession(['two_factor_passed' => true])->get('/dashboard/profile')->assertOk()->assertSee('At least 12 characters');
    }

    public function test_menus_link_to_the_profile_and_the_dashboard_has_an_edit_link(): void
    {
        $this->actingAs($this->user())->get('/dashboard/parent')->assertSee(route('profile.edit'))->assertSee('Edit profile');
        $this->actingAs(TutorProfile::factory()->approved()->create()->user)->get('/dashboard/tutor')->assertSee(route('profile.edit'));
    }

    public function test_name_and_phone_can_be_changed_with_the_current_password(): void
    {
        $user = $this->user(['phone_verified_at' => now()]);

        $this->actingAs($user)->put('/dashboard/profile/details', [
            'name' => 'Sadia M. Khan', 'phone' => '0301 7654321', 'current_password' => self::PASSWORD,
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('notice');

        $user->refresh();
        $this->assertSame('Sadia M. Khan', $user->name);
        $this->assertSame('03017654321', $user->phone);
        $this->assertNull($user->phone_verified_at, 'a new number is not verified');
    }

    public function test_details_need_the_right_password_and_a_valid_number(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put('/dashboard/profile/details', ['name' => 'Hacker', 'phone' => '03017654321', 'current_password' => 'wrong-one'])
            ->assertSessionHasErrors('current_password', errorBag: 'details');
        $this->actingAs($user)->put('/dashboard/profile/details', ['name' => 'Sadia', 'phone' => '12345', 'current_password' => self::PASSWORD])
            ->assertSessionHasErrors('phone', errorBag: 'details');
        $this->actingAs($user)->put('/dashboard/profile/details', ['name' => '', 'phone' => '03017654321', 'current_password' => self::PASSWORD])
            ->assertSessionHasErrors('name', errorBag: 'details');

        $this->assertSame('Sadia Malik', $user->fresh()->name);
        $this->assertSame('03001112222', $user->fresh()->phone);
    }

    public function test_a_tutor_cannot_change_the_name_that_was_checked_against_the_id(): void
    {
        $tutor = TutorProfile::factory()->approved()->create()->user;
        $before = $tutor->name;

        $this->actingAs($tutor)->get('/dashboard/profile')->assertSee('shows on your public profile');
        $this->actingAs($tutor)->put('/dashboard/profile/details', ['name' => 'Someone Else', 'phone' => '03017654321', 'current_password' => self::PASSWORD])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($before, $tutor->fresh()->name);
        $this->assertSame('03017654321', $tutor->fresh()->phone);
    }

    public function test_email_change_waits_for_the_new_inbox_and_warns_the_old_one(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'New.Address@Example.test', 'current_password' => self::PASSWORD])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('notice');

        $user->refresh();
        $this->assertSame('sadia@example.test', $user->email, 'nothing changes until the link is opened');
        $this->assertSame('new.address@example.test', $user->pending_email);
        Notification::assertSentOnDemand(ConfirmNewEmail::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'new.address@example.test');
        Notification::assertSentTo($user, EmailChangeRequested::class);
        $this->actingAs($user)->get('/dashboard/profile')->assertSee('Waiting for you to confirm')->assertSee('new.address@example.test');
    }

    public function test_the_link_in_the_new_inbox_finishes_the_change_even_on_another_device(): void
    {
        $user = $this->user(['email_verified_at' => null, 'pending_email' => 'new@example.test']);

        $this->get(ConfirmNewEmail::url($user, 'new@example.test'))->assertRedirect(route('login'))->assertSessionHas('notice');

        $user->refresh();
        $this->assertSame('new@example.test', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertTrue($user->hasVerifiedEmail(), 'opening the link proves the inbox');
        $this->post('/login', ['login' => 'new@example.test', 'password' => self::PASSWORD])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_bad_or_old_links_do_nothing(): void
    {
        $user = $this->user(['pending_email' => 'new@example.test']);
        $good = ConfirmNewEmail::url($user, 'new@example.test');

        $this->get(str_replace('/'.sha1('new@example.test'), '/'.sha1('other@example.test'), $good))->assertForbidden(); // signature breaks
        $this->get(route('profile.email.confirm', ['user' => $user->id, 'hash' => sha1('new@example.test')]))->assertForbidden(); // not signed
        $this->get(ConfirmNewEmail::url($user, 'other@example.test'))->assertForbidden(); // signed, but not the pending address

        $user->forceFill(['pending_email' => 'second@example.test'])->save();
        $this->get($good)->assertForbidden(); // an older request stops working when a newer one is made

        $this->assertSame('sadia@example.test', $user->fresh()->email);
    }

    public function test_the_link_does_not_steal_an_email_that_another_account_took_meanwhile(): void
    {
        $user = $this->user(['pending_email' => 'new@example.test']);
        $url = ConfirmNewEmail::url($user, 'new@example.test');
        User::factory()->create(['email' => 'new@example.test']);

        $this->get($url)->assertRedirect()->assertSessionHas('notice');

        $this->assertSame('sadia@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_email_change_is_checked(): void
    {
        Notification::fake();
        $user = $this->user();
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'taken@example.test', 'current_password' => self::PASSWORD])
            ->assertSessionHasErrors('email', errorBag: 'email');
        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'sadia@example.test', 'current_password' => self::PASSWORD])
            ->assertSessionHasErrors('email', errorBag: 'email');
        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'not-an-email', 'current_password' => self::PASSWORD])
            ->assertSessionHasErrors('email', errorBag: 'email');
        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'free@example.test', 'current_password' => 'wrong-one'])
            ->assertSessionHasErrors('current_password', errorBag: 'email');

        $this->assertNull($user->fresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_a_broken_mail_server_gives_a_message_not_an_error(): void
    {
        $user = $this->user();
        Event::listen(MessageSending::class, fn () => throw new RuntimeException('SMTP is down'));

        $this->actingAs($user)->post('/dashboard/profile/email', ['email' => 'free@example.test', 'current_password' => self::PASSWORD])
            ->assertRedirect(route('profile.edit'))->assertSessionHasErrors('email', errorBag: 'email');

        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_password_change_and_other_devices_are_logged_out(): void
    {
        config(['session.driver' => 'database']);
        $user = $this->user();
        foreach (['other-device-1', 'other-device-2'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        }
        DB::table('sessions')->insert(['id' => 'someone-else', 'user_id' => User::factory()->create()->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->put('/dashboard/profile/password', [
            'current_password' => self::PASSWORD, 'password' => 'Brand-New-2026', 'password_confirmation' => 'Brand-New-2026',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('notice');

        $this->assertTrue(Hash::check('Brand-New-2026', $user->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->whereIn('id', ['other-device-1', 'other-device-2'])->count());
        $this->assertSame(1, DB::table('sessions')->where('id', 'someone-else')->count(), "another person's sessions are never touched");
    }

    public function test_password_rules(): void
    {
        $user = $this->user();
        $put = fn (array $data) => $this->actingAs($user)->put('/dashboard/profile/password', $data);

        $put(['current_password' => 'wrong-one', 'password' => 'Brand-New-2026', 'password_confirmation' => 'Brand-New-2026'])->assertSessionHasErrors('current_password', errorBag: 'password');
        $put(['current_password' => self::PASSWORD, 'password' => 'short1', 'password_confirmation' => 'short1'])->assertSessionHasErrors('password', errorBag: 'password');
        $put(['current_password' => self::PASSWORD, 'password' => 'onlylettersnonumber', 'password_confirmation' => 'onlylettersnonumber'])->assertSessionHasErrors('password', errorBag: 'password');
        $put(['current_password' => self::PASSWORD, 'password' => 'Brand-New-2026', 'password_confirmation' => 'Different-2026'])->assertSessionHasErrors('password', errorBag: 'password');
        $put(['current_password' => self::PASSWORD, 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('password', errorBag: 'password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_an_admin_needs_a_longer_password_and_the_emailed_code(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = ['current_password' => self::PASSWORD, 'password' => 'Shortish1234', 'password_confirmation' => 'Shortish1234'];

        $this->actingAs($admin)->put('/dashboard/profile/password', $payload)->assertRedirect(route('two-factor.show'));
        $this->actingAs($admin)->withSession(['two_factor_passed' => true])->put('/dashboard/profile/password', ['password' => 'Short1', 'password_confirmation' => 'Short1'] + $payload)
            ->assertSessionHasErrors('password', errorBag: 'password');
        $this->actingAs($admin)->withSession(['two_factor_passed' => true])->put('/dashboard/profile/password', $payload)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Shortish1234', $admin->fresh()->password));
    }

    public function test_guests_cannot_use_the_forms(): void
    {
        foreach ([['put', 'details'], ['post', 'email'], ['put', 'password']] as [$method, $path]) {
            $this->{$method}("/dashboard/profile/{$path}", [])->assertRedirect('/login');
        }
    }
}
