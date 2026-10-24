<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => User::factory()->parent(),
            'name' => fake()->firstName(),
            'grade' => fake()->randomElement(['Grade 5', 'Grade 8', 'Matric', 'FSc Part 1', 'O Level']),
        ];
    }
}
