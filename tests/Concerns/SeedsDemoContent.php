<?php

namespace Tests\Concerns;

use Database\Seeders\DemoTutorsSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Fresh database with the subjects and the sample tutors the public pages show. */
trait SeedsDemoContent
{
    use RefreshDatabase;

    protected function setUpSeedsDemoContent(): void
    {
        $this->seed([SubjectSeeder::class, DemoTutorsSeeder::class]);
    }
}
