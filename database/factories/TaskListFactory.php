<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskList>
 */
class TaskListFactory extends Factory
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
            'position' => 1024,
            'created_by' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
        ];
    }

    /**
     * Indicate that the list has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
