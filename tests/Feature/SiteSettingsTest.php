<?php

namespace Tests\Feature;

use App\Enums\ClassStatus;
use App\Models\Child;
use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Step 6d: admin edits contact details and the Home page numbers. */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    private function payload(array $override = []): array
    {
        $stats = [];
        foreach (['students', 'tutors', 'subjects', 'rating'] as $stat) {
            $stats[$stat] = ['mode' => 'manual', 'value' => ['students' => '2K+', 'tutors' => '40+', 'subjects' => '12', 'rating' => '4.6'][$stat]];
        }

        return $override + ['whatsapp' => '', 'support_email' => '', 'support_hours' => '', 'stats' => $stats];
    }

    public function test_only_admins_with_the_code_open_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
        $this->actingAs(User::factory()->parent()->create())->get('/admin/settings')->assertForbidden();
        $this->actingAs(User::factory()->parent()->create())->put('/admin/settings', $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/settings')->assertRedirect(route('two-factor.show'));
        $this->asAdmin()->get('/admin/settings')->assertOk()->assertSee('Home page numbers')->assertSee('10K+');
    }

    public function test_contact_details_change_across_the_site_and_can_be_cleared(): void
    {
        config(['site.whatsapp_number' => null, 'site.support_email' => 'env@example.test']);
        $admin = $this->asAdmin();

        $admin->put('/admin/settings', $this->payload([
            'whatsapp' => '0300 1234567', 'support_email' => 'Help@Example.test', 'support_hours' => 'Every day, 9 to 5',
        ]))->assertRedirect();

        $this->get('/contact')->assertSee('https://wa.me/923001234567')->assertSee('0300 1234567')
            ->assertSee('help@example.test')->assertSee('Every day, 9 to 5')->assertDontSee('env@example.test');
        $this->get('/')->assertSee('https://wa.me/923001234567');

        $admin->put('/admin/settings', $this->payload())->assertRedirect();

        $this->get('/contact')->assertSee('env@example.test')->assertDontSee('help@example.test')->assertDontSee('wa.me/923001234567');
    }

    public function test_whatsapp_number_formats_are_checked_and_cleaned(): void
    {
        $admin = $this->asAdmin();

        $admin->put('/admin/settings', $this->payload(['whatsapp' => 'call me']))->assertSessionHasErrors('whatsapp');
        $admin->put('/admin/settings', $this->payload(['whatsapp' => '12345']))->assertSessionHasErrors('whatsapp');
        $admin->put('/admin/settings', $this->payload(['support_email' => 'not-an-email']))->assertSessionHasErrors('support_email');

        $admin->put('/admin/settings', $this->payload(['whatsapp' => '+92 300-1234567']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'whatsapp_number', 'value' => '923001234567']);
        $this->assertDatabaseHas('settings', ['key' => 'whatsapp_display', 'value' => '+92 300-1234567']);
    }

    public function test_home_page_numbers_start_as_the_placeholders_and_follow_what_the_admin_types(): void
    {
        $this->get('/')->assertSee('10K+')->assertSee('Students taught');

        $this->asAdmin()->put('/admin/settings', $this->payload())->assertRedirect();

        $this->get('/')->assertSee('2K+')->assertSee('40+')->assertSee('4.6')->assertDontSee('10K+');
    }

    public function test_manual_numbers_are_validated(): void
    {
        $admin = $this->asAdmin();
        $bad = $this->payload();

        $bad['stats']['students']['value'] = 'lots';
        $admin->put('/admin/settings', $bad)->assertSessionHasErrors('stats.students.value');

        $bad = $this->payload();
        $bad['stats']['tutors']['value'] = '';
        $admin->put('/admin/settings', $bad)->assertSessionHasErrors('stats.tutors.value');

        $bad = $this->payload();
        $bad['stats']['rating']['value'] = '7';
        $admin->put('/admin/settings', $bad)->assertSessionHasErrors('stats.rating.value');

        $bad = $this->payload();
        $bad['stats']['rating']['mode'] = 'sometimes';
        $admin->put('/admin/settings', $bad)->assertSessionHasErrors('stats.rating.mode');

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_automatic_numbers_count_real_data_and_hide_zero(): void
    {
        $auto = $this->payload();
        foreach ($auto['stats'] as $stat => $row) {
            $auto['stats'][$stat] = ['mode' => 'auto', 'value' => ''];
        }
        $this->asAdmin()->put('/admin/settings', $auto)->assertRedirect();

        // Nothing real yet: no numbers, no stats block.
        $this->get('/')->assertDontSee('Students taught')->assertDontSee('Average rating');

        $tutor = TutorProfile::factory()->approved()->create(['is_demo' => false]);
        TutorProfile::factory()->approved()->create(['is_demo' => true]);
        Subject::factory()->count(3)->create(['is_active' => true]);
        $parent = User::factory()->parent()->create();
        $child = Child::factory()->create(['parent_id' => $parent->id]);
        $booking = tap(new ClassBooking, fn ($b) => $b->forceFill([
            'tutor_profile_id' => $tutor->id, 'user_id' => $parent->id, 'child_id' => $child->id, 'student_name' => 'Ali',
            'grade' => 'O Level', 'days' => ['Mon'], 'start_time' => '17:00', 'duration_minutes' => 60,
            'starts_on' => now()->subWeek()->toDateString(), 'weeks' => 4, 'rate_minor' => 180000, 'currency' => 'PKR',
            'status' => 'accepted', 'meet_link' => 'https://meet.google.com/a-b-c', 'expires_at' => now(),
        ])->save());
        foreach ([2, 3] as $daysAgo) {
            ClassSession::create(['class_booking_id' => $booking->id, 'starts_at' => now()->subDays($daysAgo), 'ends_at' => now()->subDays($daysAgo)->addHour()])
                ->forceFill(['status' => ClassStatus::Held])->save();
        }
        foreach ([5, 4] as $rating) {
            (new Review)->forceFill([
                'tutor_profile_id' => $tutor->id, 'user_id' => User::factory()->parent()->create()->id,
                'rating' => $rating, 'comment' => 'Very good classes and teacher.',
            ])->save();
        }

        $html = $this->get('/')->assertSee('Students taught')->assertSee('Verified tutors')->assertSee('Average rating')->getContent();
        $this->assertStringContainsString('<dd class="stats__value">1</dd>', $html); // one learner (two held classes, one child), one real tutor
        $this->assertStringContainsString('<dd class="stats__value">4.5</dd>', $html);
        $this->assertStringNotContainsString('10K+', $html);
    }

    public function test_admin_overview_has_the_new_cards_and_no_made_up_money(): void
    {
        $this->asAdmin()->get('/admin')->assertOk()
            ->assertSee('Complaints')->assertSee('Contact messages')->assertSee('Coming soon')
            ->assertDontSee('PKR 2.4M')->assertDontSee('1,240');
    }
}
