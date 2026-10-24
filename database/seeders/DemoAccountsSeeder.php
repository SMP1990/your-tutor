<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TutorStatus;
use App\Models\Subject;
use App\Models\User;
use App\Support\DemoTutors;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample accounts for testing (one per role + the 8 demo tutors). NEVER runs in production.
 * Emails use the reserved .test domain; the shared password comes from DEMO_PASSWORD in .env.
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $password = Hash::make(config('site.demo_password'));

        foreach (Role::cases() as $role) {
            $this->account("{$role->value}@example.test", "Demo {$role->label()}", $role, $password);
        }

        $parent = User::where('email', 'parent@example.test')->first();
        if ($parent->children()->doesntExist()) {
            $parent->children()->createMany([
                ['name' => 'Ahmed', 'grade' => 'FSc Part 1'],
                ['name' => 'Sara', 'grade' => 'Grade 6'],
            ]);
        }

        foreach (DemoTutors::all() as $demo) {
            $this->tutor($demo, $password);
        }
    }

    private function account(string $email, string $name, Role $role, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => $password,
            'role' => $role,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function tutor(array $demo, string $password): void
    {
        $user = $this->account("{$demo['slug']}@example.test", $demo['name'], Role::Tutor, $password);

        $profile = $user->tutorProfile()->firstOrNew();
        $profile->fill([
            'slug' => $demo['slug'],
            'headline' => $demo['headline'],
            'bio' => $demo['bio'],
            'city' => $demo['city'],
            'languages' => $demo['languages'],
            'levels' => $demo['levels'],
            'experience_years' => $demo['experience_years'],
            'hourly_rate_minor' => (int) preg_replace('/\D/', '', $demo['rate']) * 100,
            'currency' => 'PKR',
            'availability' => $demo['availability'],
        ]);
        $profile->forceFill(['status' => TutorStatus::Approved, 'approved_at' => now()])->save();

        $subjectIds = Subject::whereIn('name', $demo['subjects'])->pluck('id');
        $profile->subjects()->sync($subjectIds);
    }
}
