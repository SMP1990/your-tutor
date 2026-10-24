<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TutorStatus;
use App\Models\Child;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_a_role_and_new_users_default_to_student(): void
    {
        $user = User::create(['name' => 'Ali', 'email' => 'ali@example.test', 'password' => 'secret-123']);

        $this->assertSame(Role::Student, $user->fresh()->role);
        $this->assertTrue(Hash::check('secret-123', $user->password));
        $this->assertNotSame('secret-123', $user->getRawOriginal('password'));
    }

    public function test_role_cannot_be_set_through_a_form(): void
    {
        $user = User::create([
            'name' => 'Hacker', 'email' => 'h@example.test', 'password' => 'secret-123', 'role' => 'admin',
        ]);

        $this->assertSame(Role::Student, $user->fresh()->role);
    }

    public function test_parent_has_children_and_they_go_when_the_parent_is_deleted(): void
    {
        $parent = User::factory()->parent()->create();
        $parent->children()->createMany([['name' => 'Ahmed', 'grade' => 'FSc'], ['name' => 'Sara', 'grade' => 'Grade 6']]);

        $this->assertCount(2, $parent->children);
        $parent->delete();
        $this->assertSame(0, Child::count());
    }

    public function test_tutor_profile_with_subjects_and_money_in_minor_units(): void
    {
        $physics = Subject::factory()->create(['name' => 'Physics', 'slug' => 'physics']);
        $profile = TutorProfile::factory()->create(['hourly_rate_minor' => 180000]);
        $profile->subjects()->attach($physics);

        $this->assertSame(1800, $profile->hourlyRate());
        $this->assertSame('PKR', $profile->currency);
        $this->assertTrue($physics->tutors->contains($profile));
        $this->assertSame(Role::Tutor, $profile->user->role);
    }

    public function test_only_approved_tutors_are_public(): void
    {
        TutorProfile::factory()->create();                // draft
        TutorProfile::factory()->approved()->create();

        $this->assertSame(1, TutorProfile::approved()->count());
        $this->assertSame(TutorStatus::Approved, TutorProfile::approved()->first()->status);
    }

    public function test_status_cannot_be_set_through_a_form(): void
    {
        $profile = TutorProfile::factory()->create();
        $profile->update(['status' => 'approved']);

        $this->assertSame(TutorStatus::Draft, $profile->fresh()->status);
    }

    public function test_seeders_create_sample_data_and_can_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Subject::count());
        $this->assertSame(8, TutorProfile::approved()->count());
        foreach (Role::cases() as $role) {
            $this->assertTrue(User::where('email', "{$role->value}@example.test")->exists());
        }
        $this->assertSame(2, User::where('email', 'parent@example.test')->first()->children()->count());
    }

    public function test_sample_accounts_are_never_created_in_production(): void
    {
        $this->app['env'] = 'production';
        // Run the seeder directly: the artisan command would stop to ask "are you sure?" in production.
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke();

        $this->assertSame(12, Subject::count());
        // Sample tutors are allowed (flagged is_demo, removable with demo:remove); log-in accounts are not.
        $this->assertSame(8, User::count());
        $this->assertSame(8, TutorProfile::where('is_demo', true)->count());
        $this->assertFalse(User::where('email', 'admin@example.test')->exists());
    }
}
