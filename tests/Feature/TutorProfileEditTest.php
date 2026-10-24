<?php

namespace Tests\Feature;

use App\Enums\TutorStatus;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\TutorChangesReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Step 2c-2: an approved tutor edits a live profile; new subjects/education/files wait for the admin. */
class TutorProfileEditTest extends TestCase
{
    use RefreshDatabase;

    private TutorProfile $profile;

    private Subject $physics;

    private Subject $maths;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();

        $this->physics = Subject::factory()->create(['name' => 'Physics', 'slug' => 'physics']);
        $this->maths = Subject::factory()->create(['name' => 'Mathematics', 'slug' => 'mathematics']);

        $this->profile = TutorProfile::factory()->approved()->create([
            'availability' => ['Mon' => ['evening']],
            'education' => [['degree' => 'MSc Physics', 'institute' => 'University of the Punjab', 'year' => '2016']],
        ]);
        $this->profile->forceFill(['photo_path' => 'tutors/1/old.jpg', 'cnic_front_path' => 'tutors/1/documents/old.pdf'])->save();
        Storage::disk('public')->put('tutors/1/old.jpg', 'x');
        Storage::disk('local')->put('tutors/1/documents/old.pdf', 'x');
        $this->profile->subjects()->attach($this->physics);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'headline' => 'Physics tutor for FSc',
            'bio' => str_repeat('I explain numericals step by step with past papers. ', 3),
            'city' => 'Karachi',
            'rate' => '2,500',
            'languages' => ['Urdu', 'English'],
            'levels' => ['FSc / ICS / I.Com'],
            'subjects' => [$this->physics->id],
            'availability' => ['Sat' => ['morning']],
            'education' => [['degree' => 'MSc Physics', 'institute' => 'University of the Punjab', 'year' => '2016']],
            'submit' => '0',
        ];
    }

    private function save(array $data)
    {
        return $this->actingAs($this->profile->user)->put('/dashboard/tutor/application', $data);
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    public function test_approved_tutor_sees_an_editable_profile_form(): void
    {
        $this->actingAs($this->profile->user)->get('/dashboard/tutor/application')
            ->assertOk()->assertSee('Edit your profile')->assertSee('Your profile is live')->assertSee('Save changes');
        $this->actingAs($this->profile->user)->get('/dashboard/tutor')->assertSee('Edit my profile');
    }

    public function test_rate_bio_and_availability_go_live_at_once(): void
    {
        $this->save($this->form())->assertSessionHas('notice', 'Saved. Your public profile is updated.');

        $p = $this->profile->fresh();
        $this->assertSame(250000, $p->hourly_rate_minor);
        $this->assertSame('Karachi', $p->city);
        $this->assertSame(['morning'], $p->availability['Sat']);
        $this->assertSame(TutorStatus::Approved, $p->status);
        $this->assertFalse($p->hasPendingChanges());
        $this->get('/tutors/'.$p->slug)->assertOk()->assertSee('PKR 2,500')->assertSee('Karachi');
    }

    public function test_new_subject_education_and_files_wait_for_review(): void
    {
        $this->save($this->form([
            'subjects' => [$this->physics->id, $this->maths->id],
            'education' => [['degree' => 'MPhil Physics', 'institute' => 'GCU Lahore', 'year' => '2019']],
            'photo' => UploadedFile::fake()->image('new.jpg', 300, 300),
            'cnic_front' => UploadedFile::fake()->create('cnic.pdf', 100, 'application/pdf'),
        ]))->assertSessionHas('notice', fn ($n) => str_contains($n, 'waiting for our team'));

        $p = $this->profile->fresh();
        $this->assertSame(['physics'], $p->subjects()->pluck('slug')->all()); // not live yet
        $this->assertSame('MSc Physics', $p->education[0]['degree']);
        $this->assertSame('tutors/1/old.jpg', $p->photo_path);
        $this->assertSame([$this->maths->id], $p->pending_changes['subjects']);
        $this->assertSame('MPhil Physics', $p->pending_changes['education'][0]['degree']);
        Storage::disk('local')->assertExists($p->pending_changes['files']['cnic_front_path']);
        $this->assertNotNull($p->changes_submitted_at);
        $this->get('/tutors/'.$p->slug)->assertDontSee('Mathematics');

        // Saving the form again keeps the waiting subject ticked and does not lose it.
        $this->actingAs($p->user)->get('/dashboard/tutor/application')->assertSee('Waiting for review')->assertSee('new subjects (Mathematics)');
    }

    public function test_removing_a_subject_is_live_but_one_must_stay(): void
    {
        $this->profile->subjects()->attach($this->maths);

        $this->save($this->form(['subjects' => [$this->maths->id]]));
        $this->assertSame(['mathematics'], $this->profile->subjects()->pluck('slug')->all());

        $this->save($this->form(['subjects' => [$this->physics->id]]))->assertSessionHasErrors('subjects');
        $this->assertSame(['mathematics'], $this->profile->subjects()->pluck('slug')->all());
    }

    public function test_a_live_profile_cannot_be_emptied(): void
    {
        $this->save($this->form(['rate' => '', 'bio' => 'Too short', 'availability' => []]))
            ->assertSessionHasErrors(['rate', 'bio', 'availability']);
        $this->assertSame($this->profile->hourly_rate_minor, $this->profile->fresh()->hourly_rate_minor);
    }

    public function test_admin_sees_and_approves_changes(): void
    {
        $this->save($this->form([
            'subjects' => [$this->physics->id, $this->maths->id],
            'photo' => UploadedFile::fake()->image('new.jpg', 300, 300),
            'cnic_front' => UploadedFile::fake()->create('cnic.pdf', 100, 'application/pdf'),
        ]));
        $p = $this->profile->fresh();
        $newCnic = $p->pending_changes['files']['cnic_front_path'];

        $admin = $this->asAdmin();
        $admin->get('/admin/tutors?tab=changes')->assertOk()->assertSee('Profile changes (1)')->assertSee($p->user->name);
        $admin->get("/admin/tutors/{$p->id}")->assertSee('Profile changes to review')->assertSee('Mathematics');
        $admin->get("/admin/tutors/{$p->id}/documents/cnic-front?pending=1")->assertOk();

        $admin->post("/admin/tutors/{$p->id}/changes", ['decision' => 'approve'])->assertRedirect();

        $p->refresh();
        $this->assertEqualsCanonicalizing(['physics', 'mathematics'], $p->subjects()->pluck('slug')->all());
        $this->assertSame($newCnic, $p->cnic_front_path);
        $this->assertNotSame('tutors/1/old.jpg', $p->photo_path);
        Storage::disk('local')->assertMissing('tutors/1/documents/old.pdf');
        Storage::disk('public')->assertMissing('tutors/1/old.jpg');
        $this->assertFalse($p->hasPendingChanges());
        Notification::assertSentTo($p->user, TutorChangesReviewed::class, fn ($n) => $n->approved);
    }

    public function test_admin_rejects_changes_with_a_message(): void
    {
        $this->save($this->form(['cnic_front' => UploadedFile::fake()->create('cnic.pdf', 100, 'application/pdf')]));
        $p = $this->profile->fresh();
        $newCnic = $p->pending_changes['files']['cnic_front_path'];

        $this->asAdmin()->post("/admin/tutors/{$p->id}/changes", ['decision' => 'reject'])->assertSessionHasErrors('changes_note');
        $this->asAdmin()->post("/admin/tutors/{$p->id}/changes", ['decision' => 'reject', 'changes_note' => 'The CNIC photo is blurry.']);

        $p->refresh();
        $this->assertSame('tutors/1/documents/old.pdf', $p->cnic_front_path);
        Storage::disk('local')->assertMissing($newCnic);
        $this->assertFalse($p->hasPendingChanges());
        Notification::assertSentTo($p->user, TutorChangesReviewed::class, fn ($n) => ! $n->approved && $n->note === 'The CNIC photo is blurry.');
    }

    public function test_nothing_to_review_gives_conflict(): void
    {
        $this->asAdmin()->post("/admin/tutors/{$this->profile->id}/changes", ['decision' => 'approve'])->assertStatus(409);
    }
}
