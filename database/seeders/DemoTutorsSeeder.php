<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TutorStatus;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\User;
use App\Support\DemoTutors;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Sample tutors shown on the public pages until launch (owner: dummy content until real data exists).
 * Allowed on any server, flagged `is_demo`, with unusable random passwords.
 * Remove them all with `php artisan demo:remove`.
 */
class DemoTutorsSeeder extends Seeder
{
    /** Demo subject names that are not DB subjects, mapped to DB subject slugs. */
    private const SUBJECT_ALIASES = ['Python' => ['computer-science', 'coding-for-kids']];

    public function run(): void
    {
        foreach (DemoTutors::all() as $demo) {
            $user = User::firstOrNew(['email' => "{$demo['slug']}@example.test"]);
            if (! $user->exists) {
                $user->password = Hash::make(Str::random(40)); // nobody can log in as a demo tutor
            }
            $user->forceFill(['name' => $demo['name'], 'role' => Role::Tutor, 'email_verified_at' => now()])->save();

            $profile = $user->tutorProfile()->firstOrNew(); // sets user_id even when models are guarded
            $profile->fill([
                'slug' => $demo['slug'],
                'headline' => $demo['headline'],
                'bio' => $demo['bio'],
                'city' => $demo['city'],
                'languages' => $demo['languages'],
                'levels' => str_replace('FSc', 'FSc / ICS / I.Com', $demo['levels']), // same names as config('tutors.levels')
                'experience_years' => $demo['experience_years'],
                'hourly_rate_minor' => (int) preg_replace('/\D/', '', $demo['rate']) * 100,
                'currency' => 'PKR',
                'availability' => $demo['availability'],
                'education' => array_map(fn ($q) => ['degree' => $q['title'], 'institute' => $q['place'], 'year' => $q['year']], $demo['qualifications']),
            ]);
            $profile->forceFill([
                'status' => TutorStatus::Approved,
                'approved_at' => $profile->approved_at ?? now(),
                'is_demo' => true,
                'rating_avg' => $demo['rating'],
                'reviews_count' => (int) $demo['reviews_count'],
            ])->save();

            $profile->subjects()->sync($this->subjectIds($demo));
        }
    }

    private function subjectIds(array $demo): array
    {
        $slugs = [];
        foreach ($demo['subjects'] as $name) {
            array_push($slugs, ...(self::SUBJECT_ALIASES[$name] ?? [\Illuminate\Support\Str::slug($name)]));
        }
        if (preg_match('/MDCAT|ECAT/', $demo['levels'])) {
            $slugs[] = 'entry-tests';
        }

        return Subject::whereIn('slug', array_unique($slugs))->pluck('id')->all();
    }
}
