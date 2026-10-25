<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 6c: Contact-form messages are saved and read in the admin inbox. */
class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    private function send(array $data = [])
    {
        return $this->post('/contact', $data + [
            'name' => 'Sadia Malik',
            'phone' => '0300 1234567',
            'email' => 'Sadia@Example.test',
            'topic' => 'Finding a tutor',
            'message' => 'I need a Physics tutor for my son.',
        ]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    public function test_a_valid_message_is_saved(): void
    {
        $this->send()->assertRedirect(route('contact.sent'));

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Sadia Malik', 'phone' => '03001234567', 'email' => 'sadia@example.test',
            'topic' => 'Finding a tutor', 'status' => 'new', 'user_id' => null,
        ]);
    }

    public function test_a_logged_in_sender_is_linked_to_the_account(): void
    {
        $user = User::factory()->parent()->create();

        $this->actingAs($user)->post('/contact', [
            'name' => 'Sadia', 'phone' => '03001234567', 'topic' => 'Payments and refunds', 'message' => 'When do I pay for classes?',
        ])->assertRedirect(route('contact.sent'));

        $this->assertDatabaseHas('contact_messages', ['user_id' => $user->id, 'email' => null]);
    }

    public function test_invalid_messages_are_not_saved(): void
    {
        $this->send(['phone' => '', 'email' => ''])->assertSessionHasErrors(['phone', 'email']);
        $this->send(['topic' => 'Selling you something'])->assertSessionHasErrors('topic');
        $this->send(['message' => 'short'])->assertSessionHasErrors('message');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_bots_that_fill_the_hidden_field_are_ignored(): void
    {
        $this->send(['website' => 'http://spam.example'])->assertRedirect(route('contact.sent'));

        $this->assertDatabaseCount('contact_messages', 0);
        $this->get('/contact')->assertSee('name="website"', false);
    }

    public function test_only_admins_with_the_code_can_open_the_inbox(): void
    {
        $this->send();
        $message = ContactMessage::first();

        $this->get('/admin/messages')->assertRedirect('/login');
        $this->actingAs(User::factory()->parent()->create())->get('/admin/messages')->assertForbidden();
        $this->actingAs(User::factory()->parent()->create())->get(route('admin.contact.show', $message))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/messages')->assertRedirect(route('two-factor.show'));
    }

    public function test_inbox_tabs_and_opening_marks_a_message_read(): void
    {
        $this->send();
        $this->send(['name' => 'Bilal Raza', 'message' => 'How do I become a tutor here?']);
        $admin = $this->asAdmin();

        $admin->get('/admin/messages')->assertOk()->assertSee('New (2)')->assertSee('Read (0)')->assertSee('Answered (0)')
            ->assertSee('Sadia Malik')->assertSee('Bilal Raza');

        $first = ContactMessage::orderBy('id')->first();
        $admin->get(route('admin.contact.show', $first))->assertOk()
            ->assertSee('I need a Physics tutor for my son.')->assertSee('Reply on WhatsApp')->assertSee('https://wa.me/923001234567')
            ->assertSee('mailto:sadia@example.test', false);

        $this->assertSame(ContactStatus::Read, $first->fresh()->status);
        $admin->get('/admin/messages')->assertSee('New (1)')->assertSee('Read (1)');
    }

    public function test_admin_marks_answered_and_keeps_a_private_note(): void
    {
        $this->send();
        $message = ContactMessage::first();
        $admin = $this->asAdmin();

        $admin->put(route('admin.contact.update', $message), ['status' => 'banana'])->assertSessionHasErrors('status');
        $admin->put(route('admin.contact.update', $message), ['status' => 'answered', 'admin_note' => 'Replied on WhatsApp'])->assertRedirect();

        $this->assertSame(ContactStatus::Answered, $message->fresh()->status);
        $admin->get('/admin/messages?tab=answered')->assertSee('Sadia Malik');
        $admin->get(route('admin.contact.show', $message))->assertSee('Replied on WhatsApp');
    }

    public function test_whatsapp_link_handles_plus_92_numbers(): void
    {
        $this->send(['phone' => '+923001234567']);

        $this->assertSame('https://wa.me/923001234567', ContactMessage::first()->whatsappUrl());
    }
}
