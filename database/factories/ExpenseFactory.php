<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Expense;
// use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'transaction_number' => 'DK-EX-' . now()->format('Ym') . '-' . fake()->unique()->numberBetween(1, 9999),
            'date' => fake()->dateTimeBetween('-2 months', 'now'),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 50_000, 3_000_000),
            'receipt_path' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
