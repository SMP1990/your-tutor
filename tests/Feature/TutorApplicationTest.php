<?php

namespace Tests\Feature;

use App\Enums\TutorStatus;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->tutor = User::factory()->tutor()->create(['name' => 'Ayesha Rehman']);
        Subject::factory()->create(['name' => 'Physics', 'slug' => 'physics']);
    }

    private function complete(array $overrides = []): array
    {
        return $overrides + [
            'headline' => 'MSc Physics tutor for FSc and O Level',
            'bio' => str_repeat('I explain numericals step by step with past papers. ', 3),
            'city' => 'Lahore',
            'experience_years' => 7,
            'rate' => '1,800',
            'languages' => ['Urdu', 'English'],
            'levels' => ['FSc / ICS / I.Com', 'O Level'],
            'subjects' => [Subject::first()->id],
            'availability' => ['Sat' => ['morning'], 'Mon' => ['evening']],
            'education' => [['degree' => 'MSc Physics', 'institute' => 'University of the Punjab', 'year' => '2016']],
            'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
            'cnic_front' => UploadedFile::fake()->create('cnic-front.pdf', 200, 'application/pdf'),
            'cnic_back' => UploadedFile::fake()->create('cnic-back.pdf', 200, 'application/pdf'),
            'degree' => UploadedFile::fake()->create('degree.pdf', 300, 'application/pdf'),
        ];
    }

    public function test_tutor_can_open_the_form(): void
    {
        $this->actingAs($this->tutor)->get('/dashboard/tutor/application')->assertOk()->assertSee('Tutor application');
    }

    public function test_a_draft_can_be_saved_half_finished(): void
    {
        $this->actingAs($this->tutor)
            ->put('/dashboard/tutor/application', ['headline' => 'Physics tutor', 'submit' => '0'])
            ->assertRedirect(route('tutor.application'))
            ->assertSessionHas('notice', 'Your application is saved.');

        $profile = $this->tutor->fresh()->tutorProfile;
        $this->assertSame(TutorStatus::Draft, $profile->status);
        $this->assertSame('Physics tutor', $profile->headline);
        $this->assertStringStartsWith('ayesha-rehman-', $profile->slug);
    }

    public function test_full_application_is_saved_and_submitted(): void
    {
        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', $this->complete(['submit' => '1']));

        $profile = $this->tutor->fresh()->tutorProfile;
        $this->assertSame(TutorStatus::Submitted, $profile->status);
        $this->assertNotNull($profile->submitted_at);
        $this->assertSame(180000, $profile->hourly_rate_minor);
        $this->assertSame('Urdu, English', $profile->languages);
        $this->assertSame(['morning'], $profile->availability['Sat']);
        $this->assertSame([], $profile->availability['Tue']);
        $this->assertSame('MSc Physics', $profile->education[0]['degree']);
        $this->assertSame(['Physics'], $profile->subjects->pluck('name')->all());
    }

    public function test_documents_are_private_and_photo_is_public(): void
    {
        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', $this->complete());
        $profile = $this->tutor->fresh()->tutorProfile;

        Storage::disk('local')->assertExists([$profile->cnic_front_path, $profile->cnic_back_path, $profile->degree_path]);
        Storage::disk('public')->assertMissing($profile->cnic_front_path);
        Storage::disk('public')->assertExists($profile->photo_path);
        $this->assertStringNotContainsString('cnic-front', $profile->cnic_front_path); // original name not kept
    }

    public function test_cannot_submit_when_something_is_missing(): void
    {
        $data = $this->complete(['submit' => '1']);
        unset($data['cnic_back']);

        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', $data)
            ->assertSessionHas('notice', fn ($m) => str_contains($m, 'CNIC (back)'));

        $this->assertSame(TutorStatus::Draft, $this->tutor->fresh()->tutorProfile->status);
    }

    public function test_rate_must_be_within_limits(): void
    {
        $this->actingAs($this->tutor);

        $this->put('/dashboard/tutor/application', ['rate' => '300'])->assertSessionHasErrors('rate');
        $this->put('/dashboard/tutor/application', ['rate' => '25000'])->assertSessionHasErrors('rate');
    }

    public function test_wrong_file_types_are_rejected(): void
    {
        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', [
            'cnic_front' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            'photo' => UploadedFile::fake()->create('photo.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['cnic_front', 'photo']);
    }

    public function test_submitted_application_cannot_be_changed(): void
    {
        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', $this->complete(['submit' => '1']));

        $this->put('/dashboard/tutor/application', ['headline' => 'Changed'])->assertForbidden();
        $this->assertNotSame('Changed', $this->tutor->fresh()->tutorProfile->headline);
    }

    public function test_a_tutor_cannot_approve_themselves(): void
    {
        $this->actingAs($this->tutor)->put('/dashboard/tutor/application', ['headline' => 'Me', 'status' => 'approved']);

        $this->assertSame(TutorStatus::Draft, $this->tutor->fresh()->tutorProfile->status);
    }

    public function test_only_tutors_can_apply(): void
    {
        $this->actingAs(User::factory()->parent()->create())->get('/dashboard/tutor/application')->assertForbidden();
        $this->assertSame(0, TutorProfile::count());
    }
}
