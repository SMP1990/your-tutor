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
            DemoAccountsSeeder::class, // skips itself in production
        ]);
    }
}
