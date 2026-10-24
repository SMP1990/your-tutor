<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Safe to run again: seeders update existing rows.
     */
    public function run(): void
    {
        $this->call([
            SubjectSeeder::class,
            DemoTutorsSeeder::class,   // sample tutors (flagged is_demo) until launch; `demo:remove` deletes them
            DemoAccountsSeeder::class, // log-in test accounts; skips itself in production
        ]);
    }
}
