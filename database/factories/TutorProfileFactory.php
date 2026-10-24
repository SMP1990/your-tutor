<?php

namespace Database\Factories;

use App\Enums\TutorStatus;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TutorProfile>
 */
class TutorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->tutor(),
            'slug' => Str::slug(fake()->unique()->firstName().' '.Str::random(4)),
            'status' => TutorStatus::Draft,
            'headline' => fake()->sentence(6),
            'bio' => fake()->paragraph(),
            'city' => fake()->randomElement(['Lahore', 'Karachi', 'Islamabad']),
            'languages' => 'Urdu, English',
            'levels' => 'FSc · O Level',
            'experience_years' => fake()->numberBetween(1, 15),
            'hourly_rate_minor' => fake()->numberBetween(10, 30) * 10000, // PKR 1,000 - 3,000
            'currency' => 'PKR',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => TutorStatus::Approved, 'approved_at' => now()]);
    }
}
