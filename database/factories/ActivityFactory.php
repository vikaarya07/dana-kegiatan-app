<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Activity;
// use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'date' => fake()->dateTimeBetween('-2 months', '+2 months'),
            'location' => fake()->optional()->city(),
            'budget' => fake()->randomFloat(2, 500_000, 10_000_000),
            'status' => fake()->randomElement(ActivityStatus::cases()),
        ];
    }
}
