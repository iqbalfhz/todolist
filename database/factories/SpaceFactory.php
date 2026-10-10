<?php

namespace Database\Factories;

use App\Enums\SpaceRole;
use App\Enums\SpaceType;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Ruang Pribadi',
            'type' => SpaceType::Personal,
            'timezone' => 'Asia/Jakarta',
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Add the owner as the first member of every created space.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Space $space): void {
            $space->members()->attach($space->owner_id, [
                'role' => SpaceRole::Owner->value,
                'joined_at' => $space->created_at,
            ]);
        });
    }

    /**
     * Indicate that the space is shared between the owner and the given partner.
     */
    public function sharedWith(User $partner): static
    {
        return $this
            ->state(fn (array $attributes) => [
                'name' => 'Ruang Bersama',
                'type' => SpaceType::Shared,
            ])
            ->afterCreating(function (Space $space) use ($partner): void {
                $space->members()->attach($partner->id, [
                    'role' => SpaceRole::Member->value,
                    'joined_at' => $space->created_at,
                ]);
            });
    }
}
