<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'levels' => 'Matric · FSc',
            'intro' => fake()->sentence(),
            'topics' => [fake()->words(2, true), fake()->words(2, true)],
        ];
    }
}
