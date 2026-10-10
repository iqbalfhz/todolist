<?php

namespace Database\Factories;

use App\Models\SavingsEntry;
use App\Models\SavingsGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsEntry>
 */
class SavingsEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'savings_goal_id' => SavingsGoal::factory(),
            'space_id' => fn (array $attributes) => SavingsGoal::query()->findOrFail($attributes['savings_goal_id'])->space_id,
            'user_id' => fn (array $attributes) => SavingsGoal::query()->findOrFail($attributes['savings_goal_id'])->created_by,
            'amount' => 100_000,
            'entry_date' => now()->toDateString(),
        ];
    }
}
