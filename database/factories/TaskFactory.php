<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_list_id' => TaskList::factory(),
            'space_id' => fn (array $attributes) => TaskList::query()->findOrFail($attributes['task_list_id'])->space_id,
            'title' => fake()->sentence(3),
            'position' => 1024,
            'created_by' => fn (array $attributes) => TaskList::query()->findOrFail($attributes['task_list_id'])->created_by,
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
        ];
    }

    /**
     * Make the creator responsible for every task that has no assignee yet.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Task $task): void {
            if ($task->assignees()->doesntExist()) {
                $task->assignees()->attach($task->created_by);
            }
        });
    }

    /**
     * Indicate that the task has been completed by its creator.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
            'completed_by' => $attributes['created_by'],
        ]);
    }
}
