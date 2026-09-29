<?php

namespace Database\Factories;

use App\Enums\IncomeType;
use App\Models\Activity;
use App\Models\Income;
// use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
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
            'activity_id' => fake()->optional()->randomElement(
                Activity::query()->pluck('id')->all()
            ),
            'transaction_number' => 'DK-IN-' . now()->format('Ym') . '-' . fake()->unique()->numberBetween(1, 9999),
            'date' => fake()->dateTimeBetween('-2 months', 'now'),
            'type' => fake()->randomElement(IncomeType::cases()),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 100_000, 5_000_000),
            'receipt_path' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
