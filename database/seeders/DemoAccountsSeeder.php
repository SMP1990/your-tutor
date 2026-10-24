<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Log-in test accounts, one per role (student|parent|tutor|admin@example.test). NEVER runs in production.
 * The shared password comes from DEMO_PASSWORD in .env. Demo tutors are in DemoTutorsSeeder.
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
            $user = User::firstOrNew(['email' => "{$role->value}@example.test"]);
            $user->forceFill([
                'name' => "Demo {$role->label()}",
                'password' => $password,
                'role' => $role,
                'email_verified_at' => now(),
            ])->save();
        }

        $parent = User::where('email', 'parent@example.test')->first();
        if ($parent->children()->doesntExist()) {
            $parent->children()->createMany([
                ['name' => 'Ahmed', 'grade' => 'FSc Part 1'],
                ['name' => 'Sara', 'grade' => 'Grade 6'],
            ]);
        }
    }
}
