<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Support\DemoSubjects;
use Illuminate\Database\Seeder;

/** The real subject list. Safe to run on any server, and again (it updates by slug). */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoSubjects::all() as $i => $subject) {
            Subject::updateOrCreate(['slug' => $subject['slug']], [
                'name' => $subject['name'],
                'levels' => $subject['levels'],
                'intro' => $subject['intro'],
                'topics' => $subject['topics'],
                'sort_order' => $i + 1,
            ]);
        }
    }
}
