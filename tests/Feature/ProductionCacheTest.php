<?php

namespace Tests\Feature;

use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

/**
 * Production runs `php artisan optimize` (cached config and routes). Things registered in
 * routes/web.php outside route definitions are lost then; this test catches that class of bug.
 */
class ProductionCacheTest extends TestCase
{
    use SeedsDemoContent;

    protected function tearDown(): void
    {
        $this->artisan('route:clear');
        parent::tearDown();
    }

    public function test_pages_work_with_cached_routes(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        $this->refreshApplication();
        $this->artisan('migrate'); // the fresh app has a new, empty in-memory database
        $this->seed([\Database\Seeders\SubjectSeeder::class, \Database\Seeders\DemoTutorsSeeder::class]);

        $this->get('/tutors/ayesha-r')->assertOk()->assertSee('Ayesha R.');
        $this->get('/tutors/ayesha-r/demo')->assertOk();
        $this->get('/tutors/nobody')->assertNotFound();
        $this->get('/subjects/physics')->assertOk();
    }
}
