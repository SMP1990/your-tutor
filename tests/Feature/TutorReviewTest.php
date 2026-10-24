<?php

namespace Tests\Feature;

use App\Enums\TutorStatus;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\TutorApplicationUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TutorProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');

        $this->admin = User::factory()->admin()->create();
        $this->profile = TutorProfile::factory()->create();
        Storage::disk('local')->put('tutors/1/documents/cnic.pdf', '%PDF-1.4 test');
        $this->profile->forceFill([
            'status' => TutorStatus::Submitted, 'submitted_at' => now(), 'cnic_front_path' => 'tutors/1/documents/cnic.pdf',
        ])->save();
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin)->withSession(['two_factor_passed' => true]);
    }

    private function url(string $suffix = ''): string
    {
        return "/admin/tutors/{$this->profile->id}{$suffix}";
    }

    public function test_admin_sees_waiting_applications(): void
    {
        $this->asAdmin()->get('/admin/tutors')->assertOk()->assertSee($this->profile->user->name);
        $this->get($this->url())->assertOk()->assertSee('Test and interview');
    }

    public function test_only_admins_with_two_step_login_get_in(): void
    {
        $this->actingAs($this->profile->user)->get('/admin/tutors')->assertForbidden();
        $this->actingAs(User::factory()->parent()->create())->get($this->url('/documents/cnic-front'))->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/tutors')->assertRedirect(route('two-factor.show'));
    }

    public function test_admin_can_view_a_private_document_without_caching(): void
    {
        $response = $this->asAdmin()->get($this->url('/documents/cnic-front'));

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get($this->url('/documents/cnic-back'))->assertNotFound();      // not uploaded
        $this->get($this->url('/documents/admin-notes'))->assertNotFound();    // not a document
    }

    public function test_scheduling_an_interview_emails_the_tutor(): void
    {
        $this->asAdmin()->post($this->url('/review'), [
            'subject_test_result' => 'passed',
            'interview_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'interview_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertRedirect();

        $this->profile->refresh();
        $this->assertSame(TutorStatus::InterviewScheduled, $this->profile->status);
        $this->assertSame('passed', $this->profile->subject_test_result);
        $this->assertSame($this->admin->id, $this->profile->reviewed_by);
        Notification::assertSentTo($this->profile->user, TutorApplicationUpdated::class);
    }

    public function test_cannot_approve_before_test_and_interview_are_passed(): void
    {
        $this->asAdmin()->post($this->url('/decision'), ['decision' => 'approve'])->assertSessionHasErrors('decision');
        $this->assertSame(TutorStatus::Submitted, $this->profile->fresh()->status);
    }

    public function test_approve_after_test_and_interview(): void
    {
        $this->profile->forceFill(['subject_test_result' => 'passed', 'interview_result' => 'passed'])->save();

        $this->asAdmin()->post($this->url('/decision'), ['decision' => 'approve'])->assertRedirect(route('admin.tutors.index'));

        $p = $this->profile->fresh();
        $this->assertSame(TutorStatus::Approved, $p->status);
        $this->assertNotNull($p->approved_at);
        $this->assertSame(1, TutorProfile::approved()->count());
        Notification::assertSentTo($p->user, TutorApplicationUpdated::class);
    }

    public function test_reject_and_documents_needed_require_a_message(): void
    {
        $this->asAdmin()->post($this->url('/decision'), ['decision' => 'reject'])->assertSessionHasErrors('decision_note');
        $this->post($this->url('/decision'), ['decision' => 'documents_needed'])->assertSessionHasErrors('decision_note');

        $this->post($this->url('/decision'), ['decision' => 'reject', 'decision_note' => 'Degree could not be verified.']);
        $this->assertSame(TutorStatus::Rejected, $this->profile->fresh()->status);
    }

    public function test_documents_needed_reopens_the_form_for_the_tutor(): void
    {
        $this->asAdmin()->post($this->url('/decision'), [
            'decision' => 'documents_needed', 'decision_note' => 'Please upload a clearer CNIC back.',
        ]);

        $tutor = $this->profile->user;
        $this->actingAs($tutor)->get('/dashboard/tutor/application')
            ->assertOk()
            ->assertSee('Please upload a clearer CNIC back.')
            ->assertSee('Save and submit for review');
        $this->put('/dashboard/tutor/application', ['headline' => 'Updated headline'])->assertRedirect();
        $this->assertSame('Updated headline', $this->profile->fresh()->headline);
    }

    public function test_closed_applications_cannot_be_decided_again(): void
    {
        $this->profile->forceFill(['status' => TutorStatus::Rejected])->save();

        $this->asAdmin()->post($this->url('/decision'), ['decision' => 'documents_needed', 'decision_note' => 'x'])->assertStatus(409);
    }

    public function test_menu_links_to_approvals(): void
    {
        $this->asAdmin()->get('/admin')->assertSee(route('admin.tutors.index'));
    }
}
