<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Memory;
use App\Models\SavingsEntry;
use App\Models\Space;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state: a comment on a memory, written by the owner of its space.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => 'memory',
            'subject_id' => Memory::factory(),
            'space_id' => fn (array $attributes) => Memory::query()->findOrFail($attributes['subject_id'])->space_id,
            'user_id' => fn (array $attributes) => Space::query()->findOrFail($attributes['space_id'])->owner_id,
            'body' => 'Sawahnya masih hijau banget!',
        ];
    }

    /**
     * Indicate that the comment is about the given task, savings entry, or memory.
     */
    public function on(Task|SavingsEntry|Memory $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'space_id' => $subject->space_id,
        ]);
    }
}
