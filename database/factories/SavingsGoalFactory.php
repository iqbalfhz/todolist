<?php

namespace Database\Factories;

use App\Models\SavingsGoal;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'name' => fake()->words(2, true),
            'target_amount' => 1_000_000,
            'currency' => 'IDR',
            'created_by' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
        ];
    }

    /**
     * Indicate that the goal has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
